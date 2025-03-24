<?php

class DownloadAnalytics {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get total download count
     */
    public function getTotalDownloads() {
        $query = "SELECT COUNT(*) as total FROM downloads";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Get successful download count
     */
    public function getSuccessfulDownloads() {
        $query = "SELECT COUNT(*) as total FROM downloads WHERE download_status = 'completed'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Get download count by platform
     */
    public function getDownloadsByPlatform() {
        $query = "SELECT platform, COUNT(*) as count FROM downloads GROUP BY platform ORDER BY count DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get download count by version
     */
    public function getDownloadsByVersion() {
        $query = "SELECT version, COUNT(*) as count FROM downloads GROUP BY version ORDER BY count DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get download timeline (last 30 days by default)
     */
    public function getDownloadsTimeline($days = 30) {
        $query = "SELECT DATE(created_at) as date, COUNT(*) as count
                  FROM downloads
                  WHERE created_at >= DATE(?, '-' || ? || ' days')
                  GROUP BY DATE(created_at)
                  ORDER BY date ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([date('Y-m-d'), $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total download size
     */
    public function getTotalDownloadSize() {
        $query = "SELECT SUM(file_size) as total_size FROM downloads WHERE file_size IS NOT NULL";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_size'] ?? 0;
    }

    /**
     * Get top downloading countries
     */
    public function getTopCountries($limit = 5) {
        // This would require IP geolocation which is not implemented yet
        // For now, return sample data or empty array
        return [];
    }

    /**
     * Get browser statistics
     */
    public function getBrowserStats() {
        $query = "SELECT
                    CASE
                        WHEN user_agent LIKE '%Chrome%' THEN 'Chrome'
                        WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
                        WHEN user_agent LIKE '%Safari%' THEN 'Safari'
                        WHEN user_agent LIKE '%Edge%' THEN 'Edge'
                        ELSE 'Other'
                    END as browser,
                    COUNT(*) as count
                  FROM downloads
                  WHERE user_agent IS NOT NULL
                  GROUP BY browser
                  ORDER BY count DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get detailed download records with filtering and pagination
     */
    public function getDownloadRecords($filters = [], $page = 1, $perPage = 10) {
        $query = "SELECT d.*, u.email as user_email, u.username
                  FROM downloads d
                  LEFT JOIN users u ON d.user_id = u.id
                  WHERE 1=1";
        $params = [];

        // Apply filters
        if (!empty($filters['platform'])) {
            $query .= " AND d.platform = ?";
            $params[] = $filters['platform'];
        }

        if (!empty($filters['version'])) {
            $query .= " AND d.version = ?";
            $params[] = $filters['version'];
        }

        if (!empty($filters['start_date'])) {
            $query .= " AND d.created_at >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $query .= " AND d.created_at <= ?";
            $params[] = $filters['end_date'];
        }

        if (!empty($filters['status'])) {
            $query .= " AND d.download_status = ?";
            $params[] = $filters['status'];
        }

        // Get total count for pagination
        $countQuery = str_replace("d.*, u.email as user_email, u.username", "COUNT(*) as total", $query);
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Add sorting and pagination
        $query .= " ORDER BY d.created_at DESC LIMIT ? OFFSET ?";
        $offset = ($page - 1) * $perPage;
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => $totalCount,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($totalCount / $perPage),
            'records' => $records
        ];
    }

    /**
     * Get download statistics for a specific user
     */
    public function getUserDownloadStats($userId) {
        $query = "SELECT
                    COUNT(*) as total_downloads,
                    MAX(created_at) as last_download,
                    COUNT(DISTINCT platform) as platforms_count,
                    GROUP_CONCAT(DISTINCT platform) as platforms
                  FROM downloads
                  WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
