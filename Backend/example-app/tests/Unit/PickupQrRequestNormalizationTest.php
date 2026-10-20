<?php

namespace Tests\Unit;

use App\Http\Requests\Sell\CheckCustomerCodeRequest;
use App\Http\Requests\Sell\CompleteSellRequest;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PickupQrRequestNormalizationTest extends TestCase
{
    /**
     * Helper para inicializar y validar un CheckCustomerCodeRequest.
     */
    protected function validateCheckRequest(mixed $pickupCode): array
    {
        $payload = is_null($pickupCode) ? [] : ['pickup_code' => $pickupCode];
        $request = CheckCustomerCodeRequest::create('/api/check-customer-code', 'POST', $payload);
        $request->setContainer(app());
        $request->setRedirector(app(\Illuminate\Routing\Redirector::class));
        $request->validateResolved();

        return $request->validated();
    }

    /**
     * Helper para inicializar y validar un CompleteSellRequest.
     */
    protected function validateCompleteRequest(mixed $pickupCode): array
    {
        $payload = is_null($pickupCode) ? [] : ['pick_up_code' => $pickupCode];
        $request = CompleteSellRequest::create('/api/complete-sell/1', 'POST', $payload);
        $request->setContainer(app());
        $request->setRedirector(app(\Illuminate\Routing\Redirector::class));
        $request->validateResolved();

        return $request->validated();
    }

    public function test_it_normalizes_standard_qr_payload_with_sell_id(): void
    {
        $data = $this->validateCheckRequest('TAT:2X3Y-4Z5A-6B7C:42');
        $this->assertEquals('2X3Y-4Z5A-6B7C', $data['pickup_code']);

        $completeData = $this->validateCompleteRequest('TAT:2X3Y-4Z5A-6B7C:42');
        $this->assertEquals('2X3Y-4Z5A-6B7C', $completeData['pick_up_code']);
    }

    public function test_it_normalizes_qr_payload_in_lowercase_and_with_surrounding_spaces(): void
    {
        $data = $this->validateCheckRequest('   tat:2x3y-4z5a-6b7c:99   ');
        $this->assertEquals('2X3Y-4Z5A-6B7C', $data['pickup_code']);

        $completeData = $this->validateCompleteRequest('   tat:2x3y-4z5a-6b7c:99   ');
        $this->assertEquals('2X3Y-4Z5A-6B7C', $completeData['pick_up_code']);
    }

    public function test_it_normalizes_qr_payload_without_sell_id(): void
    {
        $data = $this->validateCheckRequest('TAT:2X3Y-4Z5A-6B7C');
        $this->assertEquals('2X3Y-4Z5A-6B7C', $data['pickup_code']);

        $completeData = $this->validateCompleteRequest('TAT:2X3Y-4Z5A-6B7C');
        $this->assertEquals('2X3Y-4Z5A-6B7C', $completeData['pick_up_code']);
    }

    public function test_it_normalizes_qr_payload_with_multiple_segments(): void
    {
        $data = $this->validateCheckRequest('TAT:2X3Y-4Z5A-6B7C:42:EXTRA:INFO');
        $this->assertEquals('2X3Y-4Z5A-6B7C', $data['pickup_code']);

        $completeData = $this->validateCompleteRequest('TAT:2X3Y-4Z5A-6B7C:42:EXTRA:INFO');
        $this->assertEquals('2X3Y-4Z5A-6B7C', $completeData['pick_up_code']);
    }

    public function test_it_normalizes_plain_pickup_code_in_lowercase_and_with_spaces(): void
    {
        $data = $this->validateCheckRequest('   abcd-efgh-jklm   ');
        $this->assertEquals('ABCD-EFGH-JKLM', $data['pickup_code']);

        $completeData = $this->validateCompleteRequest('   abcd-efgh-jklm   ');
        $this->assertEquals('ABCD-EFGH-JKLM', $completeData['pick_up_code']);
    }

    public function test_it_rejects_empty_payload_after_tat_prefix(): void
    {
        $this->expectException(ValidationException::class);
        $this->validateCheckRequest('TAT:');
    }

    public function test_it_rejects_empty_pickup_code_between_tat_and_id(): void
    {
        $this->expectException(ValidationException::class);
        $this->validateCheckRequest('TAT::42');
    }

    public function test_it_rejects_empty_string_or_whitespace(): void
    {
        $this->expectException(ValidationException::class);
        $this->validateCheckRequest('     ');
    }

    public function test_it_rejects_missing_field(): void
    {
        $this->expectException(ValidationException::class);
        $this->validateCheckRequest(null);
    }

    public function test_it_safely_handles_array_type_juggling_without_fatal_error(): void
    {
        $this->expectException(ValidationException::class);
        $this->validateCheckRequest(['TAT:2X3Y-4Z5A-6B7C:42']);
    }

    public function test_it_safely_handles_boolean_type_juggling(): void
    {
        $this->expectException(ValidationException::class);
        $this->validateCheckRequest(true);
    }

    public function test_it_safely_handles_integer_type_juggling_in_complete_request(): void
    {
        $this->expectException(ValidationException::class);
        $this->validateCompleteRequest(['pick_up_code' => ['nested']]);
    }
}
