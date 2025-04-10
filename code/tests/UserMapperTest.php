<?php

use Pasha234\Hw12\Db\IdentityMap;
use Pasha234\Hw12\Db\User;
use Pasha234\Hw12\Db\UserMapper;
use Pasha234\Hw12\Db\EntityCollection;
use PDO;
use PHPUnit\Framework\TestCase;

class UserMapperTest extends TestCase
{
    private ?PDO $pdo;
    private UserMapper $userMapper;
    private IdentityMap $identityMap;

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

        $this->identityMap = new IdentityMap();

        $this->userMapper = new UserMapper($this->pdo, $this->identityMap);

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

    /**
     * @test
     */
    public function identity_map_returns_same_instance_for_same_id()
    {
        // Arrange: Вставляем пользователя напрямую или через маппер
        $email = 'identity.test@example.com';
        $userToInsert = new User([
            'first_name' => 'Identity',
            'last_name' => 'Test',
            'email' => $email,
            'password' => 'testpass',
        ]);
        $this->userMapper->save($userToInsert);
        $userId = $userToInsert->getId();
        $this->assertNotNull($userId);

        // Act: Получаем пользователя дважды
        echo "\nFetching user $userId for the first time...\n"; // Для отладки
        $user1 = $this->userMapper->find($userId);

        echo "Fetching user $userId for the second time...\n"; // Для отладки
        $user2 = $this->userMapper->find($userId);

        // Assert: Проверяем, что оба раза вернулся один и тот же объект
        $this->assertInstanceOf(User::class, $user1);
        $this->assertInstanceOf(User::class, $user2);
        $this->assertSame(
            $user1,
            $user2,
            "Finding the same user ID twice should return the exact same object instance from Identity Map."
        );
    }

    /**
     * @test
     */
    public function identity_map_works_with_find_all()
    {
        // Arrange: Вставляем несколько пользователей
        $user1Data = ['first_name' => 'FindAll', 'last_name' => 'Test1', 'email' => 'fa1@example.com', 'password' => 'pass'];
        $user2Data = ['first_name' => 'FindAll', 'last_name' => 'Test2', 'email' => 'fa2@example.com', 'password' => 'pass'];
        $user1 = new User($user1Data);
        $user2 = new User($user2Data);
        $this->userMapper->save($user1);
        $this->userMapper->save($user2);
        $user1Id = $user1->getId();
        $user2Id = $user2->getId();
        $this->identityMap = new IdentityMap();
        $this->userMapper = new UserMapper($this->pdo, $this->identityMap);

        // Act: Сначала получим одного пользователя через find()
        $user1FromFind = $this->userMapper->find($user1Id);
        $this->assertTrue($this->identityMap->has(User::class, $user1Id), "User 1 should be in map after find()");
        $this->assertFalse($this->identityMap->has(User::class, $user2Id), "User 2 should NOT be in map yet");

        // Теперь получим всех через findAll()
        $allUsersCollection = $this->userMapper->findAll();
        $this->assertTrue($this->identityMap->has(User::class, $user2Id), "User 2 should BE in map after findAll()");

        // Assert: Найдем первого пользователя в коллекции и сравним экземпляры
        $user1FromFindAll = null;
        foreach ($allUsersCollection->toArray() as $user) { // Предполагаем, что у коллекции есть toArray()
            if ($user->getId() === $user1Id) {
                $user1FromFindAll = $user;
                break;
            }
        }

        $this->assertNotNull($user1FromFindAll, "User 1 should be found in the findAll collection");
        $this->assertSame(
            $user1FromFind,
            $user1FromFindAll,
            "User instance from find() should be the same instance found in findAll() if ID matches."
        );
    }

     /**
      * @test
      */
    public function saving_updates_instance_in_identity_map()
    {
        // Arrange
        $userToInsert = new User([
        'first_name' => 'Update',
        'last_name' => 'MapTest',
        'email' => 'updatemap@example.com',
        'password' => 'initial',
        ]);
        $this->userMapper->save($userToInsert);
        $userId = $userToInsert->getId();

        // Act: Получаем, изменяем, сохраняем
        /** @var User $user1 */
        $user1 = $this->userMapper->find($userId);
        $user1->setFirstName('UPDATED');
        $this->userMapper->save($user1);

        // Получаем снова (должен быть тот же объект из карты)
        /** @var User $user2 */
        $user2 = $this->userMapper->find($userId);

        // Assert
        $this->assertSame($user1, $user2, "Should return the same instance after saving.");
        $this->assertEquals('UPDATED', $user2->getFirstName(), "The instance from the map should reflect the saved changes.");
    }
}