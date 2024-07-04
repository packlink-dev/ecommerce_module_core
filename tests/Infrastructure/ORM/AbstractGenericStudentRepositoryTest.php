<?php

namespace Logeecom\Tests\Infrastructure\ORM;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\Entity\StudentEntity;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractGenericTest.
 *
 * @package Logeecom\Tests\Infrastructure\ORM
 */
abstract class AbstractGenericStudentRepositoryTest extends TestCase
{
    protected $femaleStudents = 2;
    protected $maleStudents = 8;
    protected $studentCount = 10;

    /**
     * @return string
     */
    abstract public function getStudentEntityRepositoryClass();

    /**
     * Cleans up all storage services used by repositories
     */
    abstract public function cleanUpStorage();

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testRegisteredRepositories()
    {
        $studentRepo = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $this->assertInstanceOf(
            "\\Logeecom\\Infrastructure\\ORM\\Interfaces\\RepositoryInterface",
            $studentRepo,
            'Student repository must be instance of RepositoryInterface'
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testStudentMassInsert()
    {
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());

        foreach ($this->readStudentsFromFile() as $entity) {
            $id = $repository->save($entity);
            $this->assertGreaterThan(0, $id);
        }
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testStudentUpdate()
    {
        $this->testStudentMassInsert();
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $queryFilter = new QueryFilter();
        $queryFilter->where('email', '=', 'Brandon.Adair@powerschool.com');
        /** @var \Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\Entity\StudentEntity $student */
        $student = $repository->selectOne($queryFilter);

        $studentId = $student->getId();
        $student->email = 'Test' . $student->email;
        $repository->update($student);

        $queryFilter = new QueryFilter();
        $queryFilter->where('email', '=', 'TestBrandon.Adair@powerschool.com');
        $student = $repository->selectOne($queryFilter);
        $this->assertEquals($studentId, $student->getId());

        $student->email = 'Brandon.Adair@powerschool.com';
        $repository->update($student);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryAllStudents()
    {
        $this->testStudentMassInsert();
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());

        $this->assertCount($this->studentCount, $repository->select());
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersString()
    {
        $this->testStudentMassInsert();
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $queryFilter = new QueryFilter();
        $queryFilter->where('gender', '=', 'F');

        $this->assertCount($this->femaleStudents, $repository->select($queryFilter));

        $queryFilter = new QueryFilter();
        $queryFilter->where('gender', '!=', 'F');
        $this->assertCount($this->studentCount - $this->femaleStudents, $repository->select($queryFilter));
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testQueryWithFiltersInt()
    {
        $this->testStudentMassInsert();
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $queryFilter = new QueryFilter();
        $queryFilter->where('localId', '<', 20);

        $entities = $repository->select($queryFilter);
        $this->assertLessThan(20, count($entities));
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithOr()
    {
        $this->testStudentMassInsert();
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $queryFilter = new QueryFilter();

        $queryFilter->where('localId', '=', 3)
            ->orWhere('localId', '=', 4);

        $entities = $repository->select($queryFilter);
        $this->assertCount(2, $entities);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithAndAndOr()
    {
        $this->testStudentMassInsert();
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $queryFilter = new QueryFilter();

        $queryFilter->where('localId', '=', 3)
            ->where('gender', '=', 'M')
            ->orWhere('localId', '=', 4);

        $entities = $repository->select($queryFilter);
        $this->assertCount(2, $entities);

        $queryFilter = new QueryFilter();

        $queryFilter->where('localId', '=', 3)
            ->where('gender', '!=', 'M')
            ->orWhere('localId', '=', 4);

        $entities = $repository->select($queryFilter);
        $this->assertCount(1, $entities);

        $queryFilter = new QueryFilter();

        $queryFilter->where('localId', '=', 3)
            ->where('gender', '!=', 'M')
            ->orWhere('localId', '=', 4)
            ->where('gender', '=', 'F');

        $entities = $repository->select($queryFilter);
        $this->assertCount(0, $entities);

        $queryFilter = new QueryFilter();

        $queryFilter->where('localId', '=', 3)
            ->where('gender', '!=', 'M')
            ->orWhere('localId', '=', 4)
            ->where('gender', '=', 'F')
            ->orWhere('localId', '=', 5);

        $entities = $repository->select($queryFilter);
        $this->assertCount(1, $entities);
        /** @var StudentEntity $student */
        $student = $entities[0];
        $this->assertEquals(5, $student->localId);
    }

    /**
     * Tests repository implementation with NOT_EQUALS operator.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithNotEquals()
    {
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $this->testStudentMassInsert();

        $count = count($this->readStudentsFromFile());

        $query = new QueryFilter();
        $query->where('localId', Operators::NOT_EQUALS, 4);

        $students = $repository->select($query);
        $this->assertCount($count - 1, $students);

        $query->where('localId', Operators::EQUALS, 4);

        $students = $repository->select($query);
        $this->assertCount(0, $students);

        $query = new QueryFilter();
        $query->where('localId', Operators::NOT_EQUALS, 4);
        $query->orWhere('localId', Operators::NOT_EQUALS, 7);

        $students = $repository->select($query);
        $this->assertCount($count, $students);

        $query = new QueryFilter();
        $query->where('localId', Operators::NOT_EQUALS, 4);
        $query->where('localId', Operators::NOT_EQUALS, 7);

        $students = $repository->select($query);
        $this->assertCount($count - 2, $students);
    }

    /**
     * Test base repository with GREATER_THAN operator.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithGreaterThan()
    {
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $this->testStudentMassInsert();

        $query = new QueryFilter();
        $query->where('localId', Operators::GREATER_THAN, 5);

        $students = $repository->select($query);
        /** @var StudentEntity $student */
        foreach ($students as $student) {
            $this->assertGreaterThan(5, $student->localId);
        }

        $query->where('localId', Operators::GREATER_THAN, 7);
        $students = $repository->select($query);

        /** @var StudentEntity $student */
        foreach ($students as $student) {
            $this->assertGreaterThan(7, $student->localId);
        }

        $query->orWhere('localId', Operators::GREATER_THAN, 4);

        /** @var StudentEntity $student */
        foreach ($students as $student) {
            $this->assertGreaterThan(4, $student->localId);
        }
    }

    /**
     * Tests repository with LESS_THAN operator.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithLessThan()
    {
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $this->testStudentMassInsert();

        $query = new QueryFilter();
        $query->where('localId', Operators::LESS_THAN, 7);

        $students = $repository->select($query);
        /** @var StudentEntity $student */
        foreach ($students as $student) {
            $this->assertLessThan(7, $student->localId);
        }

        $query->where('localId', Operators::LESS_THAN, 5);
        $students = $repository->select($query);

        /** @var StudentEntity $student */
        foreach ($students as $student) {
            $this->assertLessThan(5, $student->localId);
        }

        $query->orWhere('localId', Operators::LESS_THAN, 4);

        /** @var StudentEntity $student */
        foreach ($students as $student) {
            $this->assertLessThan(5, $student->localId);
        }
    }

    /**
     * Tests repository with GREATER_THAN_OR_EQUAL_THAN operator.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithGreaterEqualThan()
    {
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $this->testStudentMassInsert();

        $query = new QueryFilter();
        $query->where('localId', Operators::GREATER_OR_EQUAL_THAN, 5);

        $students = $repository->select($query);
        /** @var StudentEntity $student */
        foreach ($students as $student) {
            $this->assertGreaterThanOrEqual(5, $student->localId);
        }

        $query->where('localId', Operators::GREATER_OR_EQUAL_THAN, 7);
        $students = $repository->select($query);

        /** @var StudentEntity $student */
        foreach ($students as $student) {
            $this->assertGreaterThanOrEqual(7, $student->localId);
        }

        $query->orWhere('localId', Operators::GREATER_OR_EQUAL_THAN, 4);

        /** @var StudentEntity $student */
        foreach ($students as $student) {
            $this->assertGreaterThanOrEqual(4, $student->localId);
        }
    }

    /**
     * Tests repository with LEST_OR_EQUAL_THAN_OPERATOR
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithLessOrEqualThan()
    {
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $this->testStudentMassInsert();

        $query = new QueryFilter();
        $query->where('localId', Operators::LESS_OR_EQUAL_THAN, 7);

        $students = $repository->select($query);
        /** @var StudentEntity $student */
        foreach ($students as $student) {
            $this->assertLessThanOrEqual(7, $student->localId);
        }

        $query->where('localId', Operators::LESS_OR_EQUAL_THAN, 5);
        $students = $repository->select($query);

        /** @var StudentEntity $student */
        foreach ($students as $student) {
            $this->assertLessThanOrEqual(5, $student->localId);
        }

        $query->orWhere('localId', Operators::LESS_OR_EQUAL_THAN, 4);

        /** @var StudentEntity $student */
        foreach ($students as $student) {
            $this->assertLessThanOrEqual(5, $student->localId);
        }
    }

    /**
     * Test repository with combined comparison operators.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithCombinedComparisonOperators()
    {
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $this->testStudentMassInsert();

        $query = new QueryFilter();
        $query->where('localId', Operators::LESS_OR_EQUAL_THAN, 7);
        $query->where('localId', Operators::GREATER_THAN, 4);

        $students = $repository->select($query);

        /** @var StudentEntity $student */
        foreach ($students as $student) {
            $this->assertLessThanOrEqual(7, $student->localId);
            $this->assertGreaterThan(4, $student->localId);
        }

        $query = new QueryFilter();
        $query->where('localId', Operators::GREATER_THAN, 7);
        $query->orWhere('localId', Operators::LESS_THAN, 5);

        $students = $repository->select($query);
        foreach ($students as $student) {
            $this->assertNotEquals(6, $student->localId);
        }
    }

    /**
     * Tests repository with IN operator.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithInOperator()
    {
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $this->testStudentMassInsert();

        $query = new QueryFilter();
        $query->where('localId', Operators::IN, array(5, 6, 7));

        $students = $repository->select($query);
        $this->assertCount(3, $students);

        /** @var StudentEntity $student */
        foreach ($students as $student) {
            $this->assertContains($student->localId, array(5, 6, 7));
        }

        $query->where('localId', Operators::IN, array(5));
        $students = $repository->select($query);
        $this->assertCount(1, $students);
        $student = $students[0];
        $this->assertEquals(5, $student->localId);

        $query->orWhere('localId', Operators::IN, array(9));
        $students = $repository->select($query);
        $this->assertCount(2, $students);

        $query->where('localId', Operators::IN, array(8));
        $students = $repository->select($query);
        $this->assertCount(1, $students);

        $student = $students[0];
        $this->assertEquals(5, $student->localId);
    }

    /**
     * Tests repository with NOT_IN operator.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithNotInOperator()
    {
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $this->testStudentMassInsert();

        $count = count($this->readStudentsFromFile());

        $query = new QueryFilter();
        $query->where('localId', Operators::NOT_IN, array(5, 6, 7));

        $students = $repository->select($query);
        $this->assertCount($count - 3, $students);

        /** @var StudentEntity $student */
        foreach ($students as $student) {
            $this->assertNotContains($student->localId, array(5, 6, 7));
        }
    }

    /**
     * Tests repository with LIKE operator.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithLikeOperator()
    {
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $this->testStudentMassInsert();

        $count = count($this->readStudentsFromFile());

        $query = new QueryFilter();
        $query->where('username', Operators::LIKE, '%g1stu%');

        $students = $repository->select($query);

        $this->assertCount($count, $students);

        $query->where('username', Operators::LIKE, '%9');
        $students = $repository->select($query);
        $this->assertCount(1, $students);
        /** @var StudentEntity $student */
        $student = $students[0];
        $this->assertStringEndsWith('9', $student->username);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testQueryWithFiltersAndSort()
    {
        $this->testStudentMassInsert();
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $queryFilter = new QueryFilter();
        $queryFilter->where('gender', Operators::EQUALS, 'M');
        $queryFilter->orderBy('email');

        $entities = $repository->select($queryFilter);
        $this->assertCount($this->maleStudents, $entities);
        $emails = array();
        /** @var StudentEntity $item */
        foreach ($entities as $item) {
            $emails[] = $item->email;
        }

        $emails2 = $emails;
        sort($emails);
        $this->assertEquals($emails, $emails2);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testQueryWithUnknownFieldSort()
    {
        $exThrown = null;
        $this->testStudentMassInsert();
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $queryFilter = new QueryFilter();
        $queryFilter->orderBy('some_field', QueryFilter::ORDER_DESC);

        try {
            $repository->select($queryFilter);
        } catch (QueryFilterInvalidParamException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testQueryWithUnIndexedFieldSort()
    {
        $exThrown = null;
        $this->testStudentMassInsert();
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $queryFilter = new QueryFilter();
        $queryFilter->orderBy('contact', QueryFilter::ORDER_DESC);

        try {
            $repository->select($queryFilter);
        } catch (QueryFilterInvalidParamException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithIdFieldSort()
    {
        $this->testStudentMassInsert();
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $queryFilter = new QueryFilter();
        $queryFilter->where('gender', Operators::EQUALS, 'M');
        $queryFilter->orderBy('id', QueryFilter::ORDER_DESC);

        $entities = $repository->select($queryFilter);
        $ids = array();
        /** @var StudentEntity $item */
        foreach ($entities as $item) {
            $ids[] = $item->getId();
        }

        $sortedIds = $ids;
        sort($sortedIds);
        $sortedIds = array_reverse($sortedIds);
        $this->assertEquals($sortedIds, $ids);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersAndLimit()
    {
        $this->testStudentMassInsert();
        $repository = RepositoryRegistry::getRepository(StudentEntity::getClassName());
        $queryFilter = new QueryFilter();
        $queryFilter->where('gender', '=', 'M');
        $queryFilter->setLimit(2);

        $entities = $repository->select($queryFilter);
        $this->assertCount(2, $entities);
    }

    /**
     * @before
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     */
    protected function before()
    {
        $this->setUp();

        RepositoryRegistry::registerRepository(StudentEntity::getClassName(), $this->getStudentEntityRepositoryClass());
    }

    /**
     * @after
     * Clean up.
     */
    protected function after()
    {
        $this->cleanUpStorage();
        $this->tearDown();
    }

    /**
     * Reads test data fixtures about students from file
     *
     * @return StudentEntity[]
     */
    protected function readStudentsFromFile()
    {
        $students = array();
        $json = file_get_contents(__DIR__ . '/../Common/EntityData/Students.json');
        $studentsRaw = json_decode($json, true);
        $this->femaleStudents = 0;
        $this->maleStudents = 0;
        foreach ($studentsRaw as $item) {
            $student = new StudentEntity();
            $student->localId = $item['local_id'];
            $student->username = $item['student_username'];
            $student->firstName = $item['name']['first_name'];
            $student->lastName = $item['name']['last_name'];
            $student->gender = $item['demographics']['gender'];
            $student->email = $item['contact_info']['email'];
            $student->addresses = $item['addresses'];
            $student->demographics = $item['demographics'];
            $student->alerts = $item['alerts'];
            $student->schoolEnrollment = $item['school_enrollment'];
            $student->contact = $item['contact'];

            if ($student->gender === 'F') {
                $this->femaleStudents++;
            } elseif ($student->gender === 'M') {
                $this->maleStudents++;
            }

            $students[] = $student;
        }

        return $students;
    }
}
