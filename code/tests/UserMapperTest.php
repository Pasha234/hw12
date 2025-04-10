<?php

use Pasha234\Hw12\Db\User;
use Pasha234\Hw12\Db\UserMapper;
use Pasha234\Hw12\Db\EntityCollection;
use PDO;
use PHPUnit\Framework\TestCase;

class UserMapperTest extends TestCase
{
    private ?PDO $pdo;
    private UserMapper $userMapper;

    protected function setUp(): void
    {
        parent::setUp();

        // PostgreSQL connection details
        $host = 'postgres'; // Change if your PostgreSQL server is on a different host
        $port = '5432';      // Default PostgreSQL port
        $dbname = 'test';    // Your database name
        $user = 'root';  // Your PostgreSQL user
        $password = 'root'; // Your PostgreSQL password

        try {
            $this->pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->fail("Connection failed: " . $e->getMessage());
        }

        // Create the 'users' table (if it doesn't exist)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                first_name VARCHAR(255) NOT NULL,
                last_name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL
            )
        ");

        $this->userMapper = new UserMapper($this->pdo);

        // Clear table before each test
        $this->pdo->exec("DELETE FROM users");
    }

    protected function tearDown(): void
    {
        // Drop the table after each test (optional, but good practice for clean isolation)
        // $this->pdo->exec("DROP TABLE users");

        $this->pdo = null;
        parent::tearDown();
    }

    public function test_can_insert_row()
    {
        $user = new User([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
        ]);

        $result = $this->userMapper->save($user);
        $this->assertTrue($result);

        // Check if the user was inserted correctly
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(['john.doe@example.com']);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($userData);
        $this->assertEquals('John', $userData['first_name']);
        $this->assertEquals('Doe', $userData['last_name']);
        $this->assertEquals('john.doe@example.com', $userData['email']);
        $this->assertEquals('password123', $userData['password']);
    }

    public function test_can_get_row_by_id()
    {
        // Insert a user first
        $this->pdo->prepare("
            INSERT INTO users (first_name, last_name, email, password)
            VALUES (?, ?, ?, ?)
        ")->execute(['John', 'Doe', 'john.doe@example.com', 'password123']);

        $lastId = $this->pdo->lastInsertId();

        // Get the user by ID
        $user = $this->userMapper->find((int)$lastId);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John', $user->toArray()['first_name']);
        $this->assertEquals('Doe', $user->toArray()['last_name']);
        $this->assertEquals('john.doe@example.com', $user->toArray()['email']);
        $this->assertEquals('password123', $user->toArray()['password']);
    }

    public function test_can_get_all_rows()
    {
        // Insert some users
        $this->pdo->prepare("
            INSERT INTO users (first_name, last_name, email, password)
            VALUES (?, ?, ?, ?)
        ")->execute(['John', 'Doe', 'john.doe@example.com', 'password123']);

        $this->pdo->prepare("
            INSERT INTO users (first_name, last_name, email, password)
            VALUES (?, ?, ?, ?)
        ")->execute(['Jane', 'Smith', 'jane.smith@example.com', 'another_password']);

        // Get all users
        $users = $this->userMapper->findAll();

        $this->assertInstanceOf(EntityCollection::class, $users);
        $this->assertCount(2, $users->toArray());

        $firstUser = $users->get(0);
        $this->assertInstanceOf(User::class, $firstUser);
        $this->assertEquals('John', $firstUser->toArray()['first_name']);

        $secondUser = $users->get(1);
        $this->assertInstanceOf(User::class, $secondUser);
        $this->assertEquals('Jane', $secondUser->toArray()['first_name']);
    }

    public function test_can_update()
    {
        // Insert a user first
        $this->pdo->prepare("
            INSERT INTO users (first_name, last_name, email, password)
            VALUES (?, ?, ?, ?)
        ")->execute(['John', 'Doe', 'john.doe@example.com', 'password123']);

        $lastId = $this->pdo->lastInsertId();

        /** @var User */
        $user = $this->userMapper->find((int)$lastId);

        // Update the user's data
        $user->setFirstName('Johnny');
        $user->setEmail('johnny.doe@example.com');

        $result = $this->userMapper->save($user);
        $this->assertTrue($result);

        // Check if the user was updated correctly
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$lastId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($userData);
        $this->assertEquals('Johnny', $userData['first_name']);
        $this->assertEquals('johnny.doe@example.com', $userData['email']);
    }

    public function test_can_delete()
    {
        // Insert a user first
        $this->pdo->prepare("
            INSERT INTO users (first_name, last_name, email, password)
            VALUES (?, ?, ?, ?)
        ")->execute(['John', 'Doe', 'john.doe@example.com', 'password123']);

        $lastId = $this->pdo->lastInsertId();

        // Get the user by ID
        $user = $this->userMapper->find((int)$lastId);

        // Delete the user
        $result = $this->userMapper->delete($user);
        $this->assertTrue($result);

        // Check if the user was deleted
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$lastId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertFalse($userData);
    }
}