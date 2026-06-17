<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Subscription;
use App\Notifications\AppointmentRequested;
use App\Notifications\AppointmentStatusUpdated;
use App\Notifications\PaymentCaptured;
use App\Services\PayPalClient;
use App\Support\ClinicalAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PayPalPaymentController extends Controller
{
    public function __construct(private PayPalClient $paypal) {}

    public function createAppointmentOrder(Request $request, Appointment $appointment): JsonResponse|RedirectResponse
    {
        abort_unless($appointment->patient_id === Auth::id(), 403);
        abort_if($appointment->payment_status === 'paid', 422, 'La cita ya está pagada.');
        abort_if(! $appointment->amount || (float) $appointment->amount <= 0, 422, 'La cita no tiene un monto válido.');

        try {
            $order = $this->paypal->createOrder(
                'appointment-'.$appointment->id,
                (float) $appointment->amount,
                'Pago de cita '.$appointment->folio,
                route('paciente.paypal.appointments.capture.redirect', $appointment),
                url('/paciente/pago-cita?appointment='.$appointment->id.'&cancelled=1'),
                config('services.paypal.currency', 'MXN')
            );

            $payment = Payment::updateOrCreate(
                ['provider_order_id' => data_get($order, 'id')],
                [
                    'user_id' => Auth::id(),
                    'appointment_id' => $appointment->id,
                    'concept' => 'Pago de cita '.$appointment->folio,
                    'amount' => $appointment->amount,
                    'currency' => config('services.paypal.currency', 'MXN'),
                    'status' => 'created',
                    'method' => 'paypal',
                    'provider' => 'paypal',
                    'reference' => 'PAYPAL-'.Str::upper(Str::random(8)),
                    'provider_payload' => $order,
                ]
            );

            $approvalUrl = $this->paypal->approvalUrl($order);
            abort_unless($approvalUrl, 422, 'PayPal no devolvió una URL de aprobación.');
            if ($request->expectsJson()) {
                return response()->json(['id' => data_get($order, 'id'), 'approval_url' => $approvalUrl, 'payment_id' => $payment->id]);
            }
            return redirect()->away($approvalUrl);
        } catch (RuntimeException $e) {
            if ($request->expectsJson()) return response()->json(['message' => $e->getMessage()], 422);
            return back()->withErrors(['paypal' => $e->getMessage()]);
        }
    }

    public function captureAppointmentOrder(Request $request, Appointment $appointment): JsonResponse|RedirectResponse
    {
        abort_unless($appointment->patient_id === Auth::id(), 403);
        $orderId = (string) ($request->input('order_id') ?: $request->query('token'));
        abort_if(! $orderId, 422, 'No se recibió la orden de PayPal.');

        try {
            $capture = $this->paypal->captureOrder($orderId);
            $status = data_get($capture, 'status');
            $captureId = data_get($capture, 'purchase_units.0.payments.captures.0.id');
            $capturedAmount = (float) data_get($capture, 'purchase_units.0.payments.captures.0.amount.value');
            $capturedCurrency = (string) data_get($capture, 'purchase_units.0.payments.captures.0.amount.currency_code');
            abort_unless($status === 'COMPLETED', 422, 'PayPal no confirmó el pago. Estado: '.$status);

            $payment = Payment::where('provider_order_id', $orderId)
                ->where('user_id', Auth::id())
                ->where('appointment_id', $appointment->id)
                ->where('status', 'created')
                ->first();
            abort_unless($payment, 422, 'La orden PayPal no corresponde a esta cita o ya fue procesada.');
            abort_unless(abs($capturedAmount - (float) $payment->amount) < 0.01 && $capturedCurrency === $payment->currency, 422, 'El importe confirmado por PayPal no coincide con la cita.');

            DB::transaction(function () use ($appointment, $orderId, $captureId, $capture, $payment) {
                $payment->fill([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'provider_capture_id' => $captureId,
                    'provider_payload' => $capture,
                ])->save();

                $appointment->update([
                    'payment_status' => 'paid',
                    'status' => $appointment->status === 'pending_payment' ? 'pending' : $appointment->status,
                ]);

                \App\Support\SafeNotifier::notify($appointment->patient, new PaymentCaptured($payment));
                if ($appointment->professional) {
                    \App\Support\SafeNotifier::notify($appointment->professional, new AppointmentRequested($appointment->fresh(['patient', 'professional'])));
                }
                ClinicalAudit::log('payment.captured', $appointment->patient_id, $payment, 'Pago PayPal capturado para cita '.$appointment->folio);
            });

            if ($request->expectsJson()) return response()->json(['ok' => true, 'redirect' => url('/paciente/gestion-citas')]);
            return redirect('/paciente/gestion-citas')->with('success', 'Pago confirmado por PayPal. Tu cita quedó registrada.');
        } catch (RuntimeException $e) {
            if ($request->expectsJson()) return response()->json(['message' => $e->getMessage()], 422);
            return redirect('/paciente/pago-cita?appointment='.$appointment->id)->withErrors(['paypal' => $e->getMessage()]);
        }
    }

    public function createSubscriptionOrder(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isProfesionalAprobado(), 403);

        $data = $request->validate(['cycle' => ['nullable', 'in:monthly,annual']]);
        $cycle = $data['cycle'] ?? $request->query('cycle', 'monthly');
        $amount = $cycle === 'annual' ? 7680.00 : 10.00;
        $label = $cycle === 'annual' ? 'Plan Anual IRIS' : 'Plan Mensual IRIS';

        try {
            $order = $this->paypal->createOrder(
                'subscription-'.$user->id.'-'.$cycle,
                $amount,
                'Suscripción profesional '.$label,
                route('profesional.paypal.subscriptions.capture.redirect', ['cycle' => $cycle]),
                url('/psicologo/pago-suscripcion?cycle='.$cycle.'&cancelled=1'),
                config('services.paypal.currency', 'MXN')
            );

            $payment = Payment::updateOrCreate(
                ['provider_order_id' => data_get($order, 'id')],
                [
                    'user_id' => $user->id,
                    'concept' => 'Suscripción '.$label,
                    'amount' => $amount,
                    'currency' => config('services.paypal.currency', 'MXN'),
                    'status' => 'created',
                    'method' => 'paypal',
                    'provider' => 'paypal',
                    'reference' => 'SUB-'.Str::upper(Str::random(8)),
                    'provider_payload' => $order,
                ]
            );

            $approvalUrl = $this->paypal->approvalUrl($order);
            abort_unless($approvalUrl, 422, 'PayPal no devolvió una URL de aprobación.');
            if ($request->expectsJson()) return response()->json(['id' => data_get($order, 'id'), 'approval_url' => $approvalUrl, 'payment_id' => $payment->id]);
            return redirect()->away($approvalUrl);
        } catch (RuntimeException $e) {
            if ($request->expectsJson()) return response()->json(['message' => $e->getMessage()], 422);
            return back()->withErrors(['paypal' => $e->getMessage()]);
        }
    }

    public function captureSubscriptionOrder(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isProfesionalAprobado(), 403);
        $orderId = (string) ($request->input('order_id') ?: $request->query('token'));
        $cycle = $request->input('cycle') ?: $request->query('cycle', 'monthly');
        abort_if(! $orderId, 422, 'No se recibió la orden de PayPal.');

        try {
            $capture = $this->paypal->captureOrder($orderId);
            $status = data_get($capture, 'status');
            $captureId = data_get($capture, 'purchase_units.0.payments.captures.0.id');
            $capturedAmount = (float) data_get($capture, 'purchase_units.0.payments.captures.0.amount.value');
            $capturedCurrency = (string) data_get($capture, 'purchase_units.0.payments.captures.0.amount.currency_code');
            abort_unless($status === 'COMPLETED', 422, 'PayPal no confirmó la suscripción. Estado: '.$status);

            $payment = Payment::where('provider_order_id', $orderId)
                ->where('user_id', $user->id)
                ->whereNull('appointment_id')
                ->where('status', 'created')
                ->first();
            abort_unless($payment, 422, 'La orden PayPal no corresponde a esta suscripción o ya fue procesada.');
            abort_unless(abs($capturedAmount - (float) $payment->amount) < 0.01 && $capturedCurrency === $payment->currency, 422, 'El importe confirmado por PayPal no coincide con la suscripción.');

            DB::transaction(function () use ($user, $orderId, $captureId, $capture, $payment) {
                $cycle = (float) $payment->amount >= 7000 ? 'annual' : 'monthly';
                $amount = (float) $payment->amount;
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'plan' => $cycle === 'annual' ? 'Plan Anual IRIS' : 'Plan Mensual IRIS',
                    'amount' => $amount,
                    'cycle' => $cycle,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => $cycle === 'annual' ? now()->addYear() : now()->addMonth(),
                    'features' => 'Gestión clínica, agenda, comunidad profesional y perfil público.',
                ]);

                Subscription::where('user_id', $user->id)
                    ->where('id', '!=', $subscription->id)
                    ->where('status', 'active')
                    ->update(['status' => 'expired']);

                $payment->fill([
                    'subscription_id' => $subscription->id,
                    'concept' => 'Suscripción '.$subscription->plan,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'provider_capture_id' => $captureId,
                    'provider_payload' => $capture,
                ])->save();

                \App\Support\SafeNotifier::notify($user, new PaymentCaptured($payment));
                ClinicalAudit::log('subscription.captured', null, $payment, 'Suscripción PayPal activada.');
            });

            if ($request->expectsJson()) return response()->json(['ok' => true, 'redirect' => url('/psicologo/dashboard-psicologo')]);
            return redirect('/psicologo/dashboard-psicologo')->with('success', 'Suscripción activada con PayPal.');
        } catch (RuntimeException $e) {
            if ($request->expectsJson()) return response()->json(['message' => $e->getMessage()], 422);
            return redirect('/psicologo/pago-suscripcion?cycle='.$cycle)->withErrors(['paypal' => $e->getMessage()]);
        }
    }
}
