<?php
namespace ControllerAction\Test\TestCase\Model\Traits;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use ControllerAction\Model\Traits\SecurityTrait;

/**
 * Regression test for POCOR-9715.
 *
 * plugins/ControllerAction/src/Model/Traits/SecurityTrait.php::getDecodedQueryArray()
 * added a referer-fallback block (POCOR-9675) that reads $decodedQuery before it is
 * guaranteed to be assigned: $decodedQuery is only set inside the
 * `elseif (isset($params['pass']))` branch, so a request satisfied by the
 * `query[queryString]`/`query[querystring]` branches instead reached the new
 * `if ($decodedQuery == null)` check on an undefined variable.
 *
 * The fix initializes $decodedQuery = null right after the query is read, before the
 * branching starts, so every branch leaves it in a defined state.
 */
class SecurityTraitTest extends TestCase
{
    /**
     * A minimal concrete class to exercise the trait in isolation, matching the shape
     * getDecodedQueryArray() expects: a public $request property (so the trait's
     * `property_exists($this, 'request')` short-circuit picks it up directly instead
     * of trying to reach a controller/table).
     */
    private function makeSubject(ServerRequest $request)
    {
        return new class ($request) {
            use SecurityTrait;

            public $request;

            public function __construct(ServerRequest $request)
            {
                $this->request = $request;
            }
        };
    }

    /**
     * Runs $callback with a temporary error handler capturing E_WARNING/E_NOTICE,
     * and returns the list of messages raised while it ran (empty = none raised).
     */
    private function capturePhpDiagnostics(callable $callback): array
    {
        $captured = [];
        set_error_handler(function ($errno, $errstr) use (&$captured) {
            $captured[] = $errstr;
            return true; // swallow it, don't fall through to the default handler
        }, E_WARNING | E_NOTICE);

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $captured;
    }

    public function testQueryStringBranchDoesNotRaiseUndefinedVariableWarning()
    {
        $request = new ServerRequest([
            'query' => ['queryString' => 'irrelevant-token'],
            'params' => [], // no 'pass' key -> the fixed branch is the one under test
        ]);
        $subject = $this->makeSubject($request);

        $diagnostics = $this->capturePhpDiagnostics(function () use ($subject) {
            $subject->getDecodedQueryArray();
        });

        $this->assertSame(
            [],
            $diagnostics,
            'getDecodedQueryArray() raised a PHP diagnostic — $decodedQuery is being read before assignment: ' . implode(' | ', $diagnostics)
        );
    }

    public function testLowercaseQuerystringBranchDoesNotRaiseUndefinedVariableWarning()
    {
        $request = new ServerRequest([
            'query' => ['querystring' => 'irrelevant-token'],
            'params' => [],
        ]);
        $subject = $this->makeSubject($request);

        $diagnostics = $this->capturePhpDiagnostics(function () use ($subject) {
            $subject->getDecodedQueryArray();
        });

        $this->assertSame([], $diagnostics);
    }

    public function testPassParamBranchStillDecodesCorrectly()
    {
        // Regression guard: the pre-existing 'pass' branch (the one that DID already
        // assign $decodedQuery) must still work unchanged after the fix.
        $request = new ServerRequest(['query' => [], 'params' => []]);
        $subject = $this->makeSubject($request);

        $encoded = $subject->paramsEncode(['institution_id' => 42]);
        $request = new ServerRequest([
            'query' => [],
            'params' => ['pass' => [$encoded]],
        ]);
        $subject = $this->makeSubject($request);

        $result = $subject->getDecodedQueryArray();

        $this->assertSame(['institution_id' => 42], $result);
    }

    public function testRefererFallbackStillDecodesWhenNoQueryOrPassPresent()
    {
        // POCOR-9675 behaviour: when there's neither a queryString/querystring query
        // param nor a pass segment, fall back to decoding the id embedded in the
        // Referer header (e.g. a modal-delete POST with no query context of its own).
        $bootstrapRequest = new ServerRequest();
        $bootstrapSubject = $this->makeSubject($bootstrapRequest);
        $encoded = $bootstrapSubject->paramsEncode(['institution_id' => 7]);

        $request = new ServerRequest([
            'query' => [],
            'params' => [],
            'environment' => [
                'HTTP_REFERER' => 'https://example.test/Institutions/StaffLeave/index/' . $encoded,
            ],
        ]);
        $subject = $this->makeSubject($request);

        $result = $subject->getDecodedQueryArray();

        $this->assertSame(['institution_id' => 7], $result);
    }
}
