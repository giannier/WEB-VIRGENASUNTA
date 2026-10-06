<?php
/**
 * IP-based sliding-window rate limiter for the Libro de Reclamaciones
 * endpoint. Backed by SQLite via PDO when available; falls back to a
 * flat-file JSON counter guarded by flock() otherwise, so the endpoint
 * keeps working even on hosts without PDO_SQLITE enabled.
 *
 * Raw IP addresses are never stored — only a salted SHA-256 hash, so a
 * leaked data file cannot be used to reconstruct visitor IPs directly.
 */

final class VaRateLimiter {
    private string $ipHash;
    private int $perHour;
    private int $perDay;
    private string $dataDir;

    public function __construct(string $ip, string $salt, int $perHour, int $perDay, string $dataDir) {
        $this->ipHash = hash('sha256', $salt . '|' . $ip);
        $this->perHour = $perHour;
        $this->perDay = $perDay;
        $this->dataDir = $dataDir;

        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0750, true);
        }
    }

    /**
     * Returns ['allowed' => bool, 'retry_after' => int|null]
     */
    public function check(): array {
        if (extension_loaded('pdo_sqlite')) {
            return $this->checkSqlite();
        }
        return $this->checkFlatFile();
    }

    private function checkSqlite(): array {
        $dbPath = $this->dataDir . '/ratelimit.sqlite';
        try {
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('CREATE TABLE IF NOT EXISTS submissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_hash TEXT NOT NULL,
                created_at INTEGER NOT NULL
            )');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ip_created ON submissions (ip_hash, created_at)');

            $now = time();
            $hourAgo = $now - 3600;
            $dayAgo = $now - 86400;

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM submissions WHERE ip_hash = :ip AND created_at >= :since');
            $stmt->execute([':ip' => $this->ipHash, ':since' => $hourAgo]);
            $hourCount = (int) $stmt->fetchColumn();

            $stmt->execute([':ip' => $this->ipHash, ':since' => $dayAgo]);
            $dayCount = (int) $stmt->fetchColumn();

            if ($hourCount >= $this->perHour || $dayCount >= $this->perDay) {
                return ['allowed' => false, 'retry_after' => 3600];
            }

            $insert = $pdo->prepare('INSERT INTO submissions (ip_hash, created_at) VALUES (:ip, :now)');
            $insert->execute([':ip' => $this->ipHash, ':now' => $now]);

            // Occasional cleanup of rows older than 24h to keep the table small.
            if (random_int(1, 20) === 1) {
                $pdo->prepare('DELETE FROM submissions WHERE created_at < :cutoff')
                    ->execute([':cutoff' => $dayAgo]);
            }

            return ['allowed' => true, 'retry_after' => null];
        } catch (Throwable $e) {
            error_log('[virgenasunta] rate limiter sqlite error: ' . $e->getMessage());
            // Fail open on infra error rather than blocking legitimate consumer complaints,
            // but still fall back to the flat-file limiter so abuse is still bounded.
            return $this->checkFlatFile();
        }
    }

    private function checkFlatFile(): array {
        $file = $this->dataDir . '/ratelimit.json';
        $fp = fopen($file, 'c+');
        if ($fp === false) {
            error_log('[virgenasunta] rate limiter: unable to open flat file store');
            return ['allowed' => true, 'retry_after' => null];
        }

        flock($fp, LOCK_EX);
        $contents = stream_get_contents($fp);
        $data = json_decode($contents ?: '{}', true);
        if (!is_array($data)) {
            $data = [];
        }

        $now = time();
        $hourAgo = $now - 3600;
        $dayAgo = $now - 86400;

        $timestamps = $data[$this->ipHash] ?? [];
        $timestamps = array_values(array_filter($timestamps, function ($ts) use ($dayAgo) {
            return $ts >= $dayAgo;
        }));

        $hourCount = count(array_filter($timestamps, function ($ts) use ($hourAgo) {
            return $ts >= $hourAgo;
        }));
        $dayCount = count($timestamps);

        if ($hourCount >= $this->perHour || $dayCount >= $this->perDay) {
            flock($fp, LOCK_UN);
            fclose($fp);
            return ['allowed' => false, 'retry_after' => 3600];
        }

        $timestamps[] = $now;
        $data[$this->ipHash] = $timestamps;

        // Trim stale IPs entirely so the file doesn't grow unbounded.
        foreach ($data as $key => $stamps) {
            $stamps = array_values(array_filter($stamps, function ($ts) use ($dayAgo) {
                return $ts >= $dayAgo;
            }));
            if (empty($stamps)) {
                unset($data[$key]);
            } else {
                $data[$key] = $stamps;
            }
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return ['allowed' => true, 'retry_after' => null];
    }

    public function ipHash(): string {
        return $this->ipHash;
    }
}
