<?php

namespace App\Services\Omnichannel;

use App\Models\AiAgent;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class AiAgentProvisioningService
{
    public function ensureDefaultForTenant(Tenant|int $tenant): AiAgent
    {
        $tenantModel = $tenant instanceof Tenant
            ? $tenant
            : Tenant::query()->findOrFail($tenant);

        return DB::transaction(function () use ($tenantModel): AiAgent {
            $default = AiAgent::query()
                ->where('tenant_id', $tenantModel->id)
                ->where('is_default', true)
                ->lockForUpdate()
                ->first();

            if ($default) {
                return $default;
            }

            $existing = AiAgent::query()
                ->where('tenant_id', $tenantModel->id)
                ->where('status', 'active')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->forceFill([
                    'is_default' => true,
                ])->save();

                return $existing->refresh();
            }

            $businessName = trim((string) ($tenantModel->company_name ?: $tenantModel->name));

            return AiAgent::query()->create([
                'tenant_id' => $tenantModel->id,
                'name' => ($businessName !== '' ? $businessName : 'Business') . ' AI Assistant',
                'status' => 'active',
                'is_default' => true,
                'instructions' => null,
                'default_language' => 'en',
                'model_settings' => [],
                'handover_settings' => [
                    'enabled' => true,
                ],
                'business_hours' => [],
            ]);
        });
    }
}
