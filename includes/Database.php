<?php
/**
 * LUXE Fashion - Database Connection Class
 * 
 * PDO Database wrapper với singleton pattern
 */

class Database
{
    private static $instance = null;
    private $pdo;
    private $statement;

    /**
     * Private constructor - Singleton pattern
     */
    private function __construct()
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Get database instance (Singleton)
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO instance
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Prepare and execute a query
     */
    public function query(string $sql, array $params = []): self
    {
        $this->statement = $this->pdo->prepare($sql);
        $this->statement->execute($params);
        return $this;
    }

    /**
     * Fetch single row
     */
    public function fetch(): ?array
    {
        return $this->statement->fetch() ?: null;
    }

    /**
     * Fetch all rows
     */
    public function fetchAll(): array
    {
        return $this->statement->fetchAll();
    }

    /**
     * Fetch single column value
     */
    public function fetchColumn()
    {
        return $this->statement->fetchColumn();
    }

    /**
     * Get row count
     */
    public function rowCount(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
