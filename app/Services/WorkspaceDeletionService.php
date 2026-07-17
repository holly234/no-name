<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customer;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class WorkspaceDeletionService
{
    /**
     * @return array{user_deleted: bool, next_business_id: ?int}
     */
    public function delete(Business $business, User $user): array
    {
        $files = $this->workspaceFiles($business);

        $result = DB::transaction(function () use ($business, $user): array {
            $nextBusinessId = $user->businesses()
                ->where('businesses.id', '!=', $business->id)
                ->orderBy('businesses.name')
                ->value('businesses.id');

            $hasOtherOwnedBusiness = $user->ownedBusinesses()
                ->where('businesses.id', '!=', $business->id)
                ->exists();
            $deleteUser = ! $nextBusinessId && ! $hasOtherOwnedBusiness && ! $user->is_platform_owner;

            $business->delete();

            return [
                'user_deleted' => $deleteUser,
                'next_business_id' => $nextBusinessId ? (int) $nextBusinessId : null,
            ];
        });

        foreach ($files as $file) {
            try {
                Storage::disk($file['disk'])->delete($file['path']);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $result;
    }

    /**
     * @return list<array{disk: string, path: string}>
     */
    private function workspaceFiles(Business $business): array
    {
        $attachments = MessageAttachment::query()
            ->where('business_id', $business->id)
            ->whereNotNull('storage_path')
            ->get(['disk', 'storage_path'])
            ->map(fn (MessageAttachment $attachment): array => [
                'disk' => $attachment->disk ?: 'local',
                'path' => $attachment->storage_path,
            ]);

        $avatars = Customer::query()
            ->where('business_id', $business->id)
            ->whereNotNull('avatar_path')
            ->get(['avatar_disk', 'avatar_path'])
            ->map(fn (Customer $customer): array => [
                'disk' => $customer->avatar_disk ?: 'public',
                'path' => $customer->avatar_path,
            ]);

        return $attachments
            ->concat($avatars)
            ->unique(fn (array $file): string => $file['disk'].'|'.$file['path'])
            ->values()
            ->all();
    }
}
