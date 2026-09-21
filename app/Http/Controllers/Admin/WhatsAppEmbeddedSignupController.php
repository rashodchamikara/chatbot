<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\Omnichannel\WhatsAppCloudApiException;
use App\Http\Controllers\Controller;
use App\Services\Omnichannel\WhatsApp\MetaEmbeddedSignupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class WhatsAppEmbeddedSignupController extends Controller
{
    public function complete(
        Request $request,
        MetaEmbeddedSignupService $service,
    ): JsonResponse {
        $user = $request->user();

        $rules = [
            'code' => ['required', 'string', 'min:8', 'max:4096'],
            'waba_id' => ['required', 'string', 'regex:/^\d+$/', 'max:191'],
            'phone_number_id' => ['required', 'string', 'regex:/^\d+$/', 'max:191'],
            'business_id' => ['nullable', 'string', 'regex:/^\d+$/', 'max:191'],
            'name' => ['nullable', 'string', 'max:191'],
        ];

        if ($user->isSuperAdmin()) {
            $rules['tenant_id'] = ['required', 'integer', 'exists:tenants,id'];
        }

        $validated = $request->validate($rules, [
            'waba_id.regex' => 'Meta returned an invalid WhatsApp Business Account ID.',
            'phone_number_id.regex' => 'Meta returned an invalid WhatsApp Phone Number ID.',
            'business_id.regex' => 'Meta returned an invalid Business Portfolio ID.',
        ]);

        $tenantId = $user->isSuperAdmin()
            ? (int) $validated['tenant_id']
            : (int) $user->tenant_id;

        if ($tenantId <= 0) {
            throw ValidationException::withMessages([
                'tenant_id' => 'This account is not assigned to a tenant.',
            ]);
        }

        try {
            $connection = $service->complete(
                tenantId: $tenantId,
                authorizationCode: $validated['code'],
                businessAccountId: $validated['waba_id'],
                phoneNumberId: $validated['phone_number_id'],
                businessId: $validated['business_id'] ?? null,
                connectionName: $validated['name'] ?? null,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (WhatsAppCloudApiException $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' =>
                    'Meta onboarding could not be completed. Please retry or use the manual connection option.',
            ], 500);
        }

        $healthy = $connection->status === 'active';

        if ($healthy) {
            session()->flash(
                'success',
                'WhatsApp connected through Meta Embedded Signup and verified successfully.'
            );
        } else {
            session()->flash(
                'warning',
                'Meta signup completed, but final WhatsApp provisioning needs attention: '
                . ($connection->last_error ?: 'Unknown provisioning error.')
            );
        }

        return response()->json([
            'success' => $healthy,
            'connection_id' => $connection->id,
            'status' => $connection->status,
            'message' => $healthy
                ? 'WhatsApp connected successfully.'
                : ($connection->last_error ?: 'WhatsApp provisioning needs attention.'),
            'redirect_url' => $healthy
                ? route('admin.channels.index')
                : route('admin.channels.whatsapp.edit', $connection),
        ]);
    }
}
