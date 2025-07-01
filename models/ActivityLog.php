<?php
require_once __DIR__ . '/../config/database.php';

class ActivityLog {
    private $conn;
    private $table = 'activity_logs';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function logActivity($userId, $action, $description = '') {
        $query = "INSERT INTO " . $this->table . " 
                 (user_id, action, description, ip_address, user_agent) 
                 VALUES (:user_id, :action, :description, :ip_address, :user_agent)";

        try {
            $stmt = $this->conn->prepare($query);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':ip_address', $ipAddress);
            $stmt->bindParam(':user_agent', $userAgent);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error logging activity: " . $e->getMessage());
            return false;
        }
    }

    public function getLogs($limit, $offset, $searchTerm = '') {
        $sql = "SELECT al.*, u.full_name 
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id";
        
        if (!empty($searchTerm)) {
            $sql .= " WHERE u.full_name LIKE :searchTerm OR al.action LIKE :searchTerm OR al.description LIKE :searchTerm";
        }

        $sql .= " ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset";
        
        try {
            $stmt = $this->conn->prepare($sql);
            
            if (!empty($searchTerm)) {
                $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
            }
            
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getLogs: " . $e->getMessage());
            return [];
        }
    }

    public function getTotalLogsCount($searchTerm = '') {
        $sql = "SELECT COUNT(al.id) 
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id";

        if (!empty($searchTerm)) {
            $sql .= " WHERE u.full_name LIKE :searchTerm OR al.action LIKE :searchTerm OR al.description LIKE :searchTerm";
        }

        try {
            $stmt = $this->conn->prepare($sql);

            if (!empty($searchTerm)) {
                $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
            }

            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error in getTotalLogsCount: " . $e->getMessage());
            return 0;
        }
    }

    public function getActivityLogs($userId = null, $userType = null, $limit = 100, $action = null, $dateFrom = null, $dateTo = null) {
        $query = "SELECT * FROM " . $this->table;
        $conditions = [];
        $params = [];

        if ($userId !== null) {
            $conditions[] = "user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if ($userType !== null) {
            $conditions[] = "user_type = :user_type";
            $params[':user_type'] = $userType;
        }

        if ($action !== null) {
            $conditions[] = "action = :action";
            $params[':action'] = $action;
        }

        if ($dateFrom !== null) {
            $conditions[] = "created_at >= :date_from";
            $params[':date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== null) {
            $conditions[] = "created_at <= :date_to";
            $params[':date_to'] = $dateTo . ' 23:59:59';
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY created_at DESC LIMIT :limit";
        $params[':limit'] = min(intval($limit), 500); // Cap at 500 records

        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => &$value) {
                if ($key === ':limit') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error retrieving activity logs: " . $e->getMessage());
            return [];
        }
    }

    public function getUniqueActions() {
        try {
            $stmt = $this->conn->query("SELECT DISTINCT action FROM {$this->table} ORDER BY action");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error retrieving unique actions: " . $e->getMessage());
            return [];
        }
    }
}