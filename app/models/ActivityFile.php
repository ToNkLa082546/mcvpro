<?php

class ActivityFile
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($data)
    {
        $sql = "INSERT INTO activity_files (activity_id, original_filename, stored_filename, file_path, uploaded_by) VALUES (:activity_id, :original_filename, :stored_filename, :file_path, :uploaded_by)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function getFilesByActivityId($activityId)
    {
        $sql = "SELECT f.*, p.fname_personal AS uploader_name 
                FROM activity_files f
                LEFT JOIN personal p ON f.uploaded_by = p.user_id
                WHERE f.activity_id = ? ORDER BY f.uploaded_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$activityId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($fileId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM activity_files WHERE file_id = ?");
        $stmt->execute([$fileId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($fileId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM activity_files WHERE file_id = ?");
        return $stmt->execute([$fileId]);
    }
}
