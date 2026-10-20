<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserState;
use App\Events\PurchaseCompleted;
use App\Listeners\SendPurchaseConfirmationEmail;
use App\Mail\PurchaseConfirmation;
use App\Models\EstablishmentType;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Sell;
use App\Models\User;
use Database\Seeders\EstablishmentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendPurchaseConfirmationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $seller;

    protected FoodEstablishment $establishment;

    protected Sell $sell;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(EstablishmentTypeSeeder::class);

        $this->customer = User::factory()->withRole(UserRole::CUSTOMER->value)->create([
            'state' => UserState::ACTIVE->value,
            'email' => 'customer@test.com',
        ]);

        $this->seller = User::factory()->withRole(UserRole::SELLER->value)->create([
            'state' => UserState::ACTIVE->value,
        ]);

        $establishmentType = EstablishmentType::inRandomOrder()->first();

        $this->establishment = FoodEstablishment::factory()->create([
            'user_id' => $this->seller->id,
            'establishment_type_id' => $establishmentType->id,
        ]);

        $this->sell = Sell::factory()->create([
            'bought_by' => $this->customer->id,
            'sold_by' => $this->establishment->id,
            'pickup_code' => 'TEST-1234-CODE',
        ]);
    }

    #[Test]
    public function it_dispatches_purchase_completed_event_correctly(): void
    {
        Event::fake([PurchaseCompleted::class]);

        PurchaseCompleted::dispatch($this->sell);

        Event::assertDispatched(PurchaseCompleted::class, function (PurchaseCompleted $event) {
            return $event->sell->id === $this->sell->id
                && $event->sell->bought_by === $this->customer->id
                && $event->sell->sold_by === $this->establishment->id
                && $event->sell->pickup_code === 'TEST-1234-CODE';
        });

        Event::assertListening(
            PurchaseCompleted::class,
            SendPurchaseConfirmationEmail::class
        );
    }

    #[Test]
    public function it_dispatches_purchase_completed_event_when_customer_buys_offers(): void
    {
        Event::fake([PurchaseCompleted::class]);

        $offer = Offer::factory()->active()->create([
            'food_establishment_id' => $this->establishment->id,
            'quantity' => 5,
            'price' => 2000,
            'expiration_datetime' => now()->addDays(2),
        ]);

        $this->actingAs($this->customer);

        $prepareResponse = $this->postJson('/api/customer/pack-reservations/prepare', [
            'food_establishment_id' => $this->establishment->id,
            'offers' => [
                [
                    'id' => $offer->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $prepareResponse->assertStatus(200);
        $purchaseToken = $prepareResponse->json('data.purchase_token');

        $buyResponse = $this->postJson('/api/customer/pack-reservations/confirm', [
            'purchase_token' => $purchaseToken,
        ]);

        $buyResponse->assertStatus(200);

        Event::assertDispatched(PurchaseCompleted::class, function (PurchaseCompleted $event) {
            return $event->sell->bought_by === $this->customer->id
                && $event->sell->sold_by === $this->establishment->id;
        });
    }

    #[Test]
    public function it_sends_purchase_confirmation_email_to_customer_email(): void
    {
        Mail::fake();

        $listener = new SendPurchaseConfirmationEmail;
        $event = new PurchaseCompleted($this->sell);

        $listener->handle($event);

        Mail::assertSent(PurchaseConfirmation::class, function (PurchaseConfirmation $mail) {
            return $mail->hasTo($this->customer->email)
                && $mail->sell->id === $this->sell->id;
        });
    }

    #[Test]
    public function it_renders_qr_code_in_purchase_confirmation_email_content(): void
    {
        $mailable = new PurchaseConfirmation($this->sell);
        $rendered = $mailable->render();

        $this->assertStringContainsString($this->sell->pickup_code, $rendered);
        $this->assertStringContainsString('Código QR de retiro', $rendered);
    }

    #[Test]
    public function it_generates_valid_png_binary_and_decodes_qr_payload_correctly(): void
    {
        $mailable = new PurchaseConfirmation($this->sell);
        $mailable->build();

        $qrBinary = $mailable->viewData['qrBinary'] ?? null;

        $this->assertNotNull($qrBinary, 'El binario del QR no debe ser nulo.');
        $this->assertNotEmpty($qrBinary, 'El binario del QR no debe estar vacío.');

        // 1. Verificación de firma mágica PNG (\x89PNG\r\n\x1a\n)
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $qrBinary, 'El archivo generado no tiene cabecera PNG válida.');

        // 2. Decodificación óptica con chillerlan QRCode
        $expectedPayload = "TAT:{$this->sell->pickup_code}:{$this->sell->id}";
        $decoded = (new \chillerlan\QRCode\QRCode)->readFromBlob($qrBinary);

        $this->assertEquals($expectedPayload, $decoded->data, 'El QR decodificado no coincide con el payload esperado.');
    }

    #[Test]
    public function it_embeds_qr_as_inline_cid_attachment(): void
    {
        $mailable = new PurchaseConfirmation($this->sell);
        $mailable->build();

        $qrBinary = $mailable->viewData['qrBinary'];
        $this->assertNotNull($qrBinary);

        // 1. Verificar que Illuminate\Mail\Message genera el CID correcto para clientes de correo (Gmail, Outlook)
        $symfonyEmail = new \Symfony\Component\Mime\Email;
        $message = new \Illuminate\Mail\Message($symfonyEmail);
        $cid = $message->embedData($qrBinary, 'pickup-qr.png', 'image/png');

        $this->assertEquals('cid:pickup-qr.png', $cid);

        $attachments = $symfonyEmail->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertEquals('pickup-qr.png', $attachments[0]->getFilename());
        $this->assertEquals('image/png', $attachments[0]->getContentType());

        // 2. Verificar que el render de la plantilla procesa el embedData correctamente
        $rendered = $mailable->render();
        $this->assertStringContainsString('alt="Código QR de retiro"', $rendered);
        // Laravel en render() reemplaza internamente los tags cid: por data-uris para visualización web
        $this->assertStringContainsString('data:image/png;base64,', $rendered);
    }
}
