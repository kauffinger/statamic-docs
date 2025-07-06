<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class UpdateDocsCommand extends Command
{
    protected $signature = 'docs:update 
                            {--no-git : Skip git pull operation}
                            {--no-index : Skip search index rebuild}
                            {--remote=upstream : Git remote to pull from}
                            {--branch=master : Branch to pull from}';

    protected $description = 'Pull latest changes from upstream and rebuild search index';

    public function handle()
    {
        $this->info('🚀 Starting documentation update process...');

        // Step 1: Git pull (unless skipped)
        if (! $this->option('no-git')) {
            $this->pullLatestChanges();
        }

        // Step 2: Clear Stache cache
        $this->clearStacheCache();

        // Step 3: Rebuild search index (unless skipped)
        if (! $this->option('no-index')) {
            $this->rebuildSearchIndex();
        }

        $this->info('✅ Documentation update completed successfully!');
    }

    private function pullLatestChanges()
    {
        $this->info('📥 Pulling latest changes from upstream...');

        $remote = $this->option('remote');
        $branch = $this->option('branch');

        // Check if we're in a git repository
        if (! is_dir(base_path('.git'))) {
            $this->error('❌ Not in a git repository. Skipping git pull.');

            return;
        }

        // Check if remote exists
        $remotes = shell_exec('git remote');
        if (! str_contains($remotes, $remote)) {
            $this->error("❌ Remote '{$remote}' not found. Available remotes:");
            $this->line($remotes);

            return;
        }

        // Get current branch
        $currentBranch = trim(shell_exec('git branch --show-current'));
        $this->info("📍 Current branch: {$currentBranch}");
        $this->info("📥 Pulling from: {$remote}/{$branch}");

        // Check for uncommitted changes
        $status = shell_exec('git status --porcelain');
        if (! empty(trim($status))) {
            $this->warn('⚠️  You have uncommitted changes:');
            $this->line($status);
            if (! $this->confirm('Continue with git pull? (Changes may be lost)')) {
                $this->error('❌ Aborted to preserve uncommitted changes.');

                return;
            }
        }

        // Fetch from upstream first
        $this->info('🔄 Fetching from '.$remote.'...');
        $fetchProcess = new Process(['git', 'fetch', $remote]);
        $fetchProcess->setWorkingDirectory(base_path());
        $fetchProcess->run();

        if (! $fetchProcess->isSuccessful()) {
            $this->error('❌ Failed to fetch from '.$remote.':');
            $this->error($fetchProcess->getErrorOutput());

            return;
        }

        // Merge upstream/master into current branch
        $this->info('🔀 Merging '.$remote.'/'.$branch.' into '.$currentBranch.'...');
        $mergeProcess = new Process(['git', 'merge', $remote.'/'.$branch]);
        $mergeProcess->setWorkingDirectory(base_path());
        $mergeProcess->run();

        if ($mergeProcess->isSuccessful()) {
            $this->info('✅ Successfully merged latest changes from '.$remote.'/'.$branch);
            $this->line($mergeProcess->getOutput());
        } else {
            $this->error('❌ Failed to merge changes:');
            $this->error($mergeProcess->getErrorOutput());
            $this->warn('💡 You may need to resolve merge conflicts manually.');
        }
    }

    private function clearStacheCache()
    {
        $this->info('🧹 Clearing Stache cache...');

        $exitCode = $this->call('statamic:stache:clear');

        if ($exitCode === 0) {
            $this->info('✅ Stache cache cleared successfully.');
        } else {
            $this->error('❌ Failed to clear Stache cache.');
        }
    }

    private function rebuildSearchIndex()
    {
        $this->info('🔍 Rebuilding search index...');

        // First, clear the existing index
        $this->call('statamic:search:update', ['--all' => true]);

        $this->info('✅ Search index rebuilt successfully.');
        $this->line('💡 The docs:search command is now ready to use with updated content.');
    }
}
