use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\InstitutionStudents;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log; // POCOR-9509

class InstitutionStudentsApiTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $user = TestSecurityUser::where('id', 2)->first();
        if (!$user) {
            $this->markTestSkipped('User with id 2 not found.');
            return;
        }
        $this->token = JWTAuth::fromUser($user);
    }

    public function test_can_list_InstitutionStudents()
    {
        if (InstitutionStudents::count() === 0) {
            InstitutionStudents::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-students');

        $response->assertStatus(200);
    }

    public function test_can_create_InstitutionStudents()
    {
        $record = InstitutionStudents::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-students', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InstitutionStudents()
    {
        $record = InstitutionStudents::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-students/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_InstitutionStudents()
    {
        $record = InstitutionStudents::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-students/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InstitutionStudents()
    {
        $record = InstitutionStudents::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/institution-students/' . $record->id);

        $response->assertStatus(204);
    }

    // POCOR-9509: Test alert triggering on new InstitutionStudent creation
    public function testAlertTriggeredOnNewInstitutionStudent()
    {
        Log::spy(); // Spy on the Log facade

        $newStudentData = InstitutionStudents::factory()->make()->toArray();
        // Ensure student_status_id is present for the alert logic
        $newStudentData['student_status_id'] = $this->faker->numberBetween(1, 10); 
        $newStudentData['institution_id'] = $this->faker->numberBetween(1, 100);
        $newStudentData['student_id'] = $this->faker->uuid(); // Assuming student_id is UUID

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-students', $newStudentData);

        $response->assertStatus(201); // Assuming successful creation
        
        Log::shouldHaveReceived('info')
            ->withArgs(function ($message) {
                return str_contains($message, '[POCOR-9509] Student status alert processed');
            })
            ->once();
    }

    // POCOR-9509: Test alert triggering on student_status_id change
    public function testAlertTriggeredOnStatusChange()
    {
        Log::spy(); // Spy on the Log facade

        $student = InstitutionStudents::factory()->create([
            'student_status_id' => 1, // Initial status
            'institution_id' => $this->faker->numberBetween(1, 100),
            'student_id' => $this->faker->uuid(),
        ]);

        $updatedData = [
            'student_status_id' => 2, // Change status
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-students/' . $student->id, $updatedData);

        $response->assertStatus(200); // Assuming successful update

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message) {
                return str_contains($message, '[POCOR-9509] Student status alert processed');
            })
            ->once();
    }

    // POCOR-9509: Test alert not triggered on other field change
    public function testAlertNotTriggeredOnOtherFieldChange()
    {
        Log::spy(); // Spy on the Log facade

        $student = InstitutionStudents::factory()->create([
            'start_date' => Carbon::now()->subYears(5)->format('Y-m-d'),
            'end_date' => Carbon::now()->subYears(1)->format('Y-m-d'),
            'institution_id' => $this->faker->numberBetween(1, 100),
            'student_id' => $this->faker->uuid(),
        ]);

        $updatedData = [
            'start_date' => Carbon::now()->subYears(4)->format('Y-m-d'), // Change a non-status field
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-students/' . $student->id, $updatedData);

        $response->assertStatus(200); // Assuming successful update

        Log::shouldNotHaveReceived('info', function ($message) {
            return str_contains($message, '[POCOR-9509] Student status alert processed');
        });
    }
}
