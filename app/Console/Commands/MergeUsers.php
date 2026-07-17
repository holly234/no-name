<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeUsers extends Command
{
    protected $signature = 'users:merge
        {source-email : Obsolete user whose workspaces and history will be transferred}
        {target-email : User that will remain after the merge}
        {--force : Perform the merge without an interactive confirmation}';

    protected $description = 'Merge an obsolete user into another user without losing workspace data or connected accounts';

    public function handle(): int
    {
        $sourceEmail = strtolower(trim((string) $this->argument('source-email')));
        $targetEmail = strtolower(trim((string) $this->argument('target-email')));

        if ($sourceEmail === $targetEmail) {
            $this->error('Source and target emails must be different.');

            return self::FAILURE;
        }

        $source = User::query()->whereRaw('LOWER(email) = ?', [$sourceEmail])->first();
        $target = User::query()->whereRaw('LOWER(email) = ?', [$targetEmail])->first();

        if (! $source || ! $target) {
            $this->error(! $source
                ? "Source user {$sourceEmail} was not found."
                : "Target user {$targetEmail} was not found.");

            return self::FAILURE;
        }

        $sourceWorkspaceCount = $source->businesses()->count();
        $connectedAccountCount = DB::table('connected_accounts')
            ->whereIn('business_id', $source->businesses()->select('businesses.id'))
            ->count();

        $this->table(['Merge item', 'Value'], [
            ['Source user', $source->email],
            ['Target user', $target->email],
            ['Source workspaces', (string) $sourceWorkspaceCount],
            ['Connected accounts preserved', (string) $connectedAccountCount],
        ]);

        if (! $this->option('force') && ! $this->confirm('Merge the source user into the target and permanently delete the source user?')) {
            $this->warn('Merge cancelled. No data was changed.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($source, $target): void {
            $sourceMemberships = DB::table('business_user')
                ->where('user_id', $source->id)
                ->get(['business_id', 'role']);

            foreach ($sourceMemberships as $membership) {
                $targetMembership = DB::table('business_user')
                    ->where('business_id', $membership->business_id)
                    ->where('user_id', $target->id)
                    ->first();

                $role = $this->higherRole($membership->role, $targetMembership?->role);

                DB::table('business_user')->updateOrInsert(
                    ['business_id' => $membership->business_id, 'user_id' => $target->id],
                    ['role' => $role, 'updated_at' => now(), 'created_at' => $targetMembership?->created_at ?? now()]
                );
            }

            DB::table('businesses')
                ->where('owner_id', $source->id)
                ->update(['owner_id' => $target->id, 'updated_at' => now()]);

            $sourceReads = DB::table('conversation_reads')
                ->where('user_id', $source->id)
                ->get();

            foreach ($sourceReads as $read) {
                $targetRead = DB::table('conversation_reads')
                    ->where('conversation_id', $read->conversation_id)
                    ->where('user_id', $target->id)
                    ->first();

                $lastReadAt = collect([$read->last_read_at, $targetRead?->last_read_at])
                    ->filter()
                    ->sortDesc()
                    ->first();

                DB::table('conversation_reads')->updateOrInsert(
                    ['conversation_id' => $read->conversation_id, 'user_id' => $target->id],
                    ['last_read_at' => $lastReadAt, 'updated_at' => now(), 'created_at' => $targetRead?->created_at ?? now()]
                );
            }

            DB::table('team_invites')
                ->where('invited_by', $source->id)
                ->update(['invited_by' => $target->id, 'updated_at' => now()]);

            if ($source->is_platform_owner && ! $target->is_platform_owner) {
                $target->forceFill(['is_platform_owner' => true])->save();
            }

            $source->delete();
        });

        $this->info("Merged {$sourceEmail} into {$targetEmail}. The source user was deleted; workspace and connection data were preserved.");

        return self::SUCCESS;
    }

    private function higherRole(?string $first, ?string $second): string
    {
        $rank = ['Agent' => 1, 'agent' => 1, 'Admin' => 2, 'admin' => 2, 'Owner' => 3, 'owner' => 3];
        $roles = array_filter([$first, $second]);

        usort($roles, fn (string $a, string $b): int => ($rank[$b] ?? 0) <=> ($rank[$a] ?? 0));

        return $roles[0] ?? 'Agent';
    }
}
