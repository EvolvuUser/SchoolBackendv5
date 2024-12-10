<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserMaster;
use Illuminate\Support\Facades\Hash;
use DB;

class RehashPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:rehash-passwords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = DB::table('user_master')->get();
        foreach ($users as $user) {
            
            $hashedPassword = Hash::make($user->password);
             
                // Update the user in the database
                DB::table('user_master')
                    ->where('user_id', $user->user_id) // Ensure you're matching the user by their ID
                    ->update(['password' => $hashedPassword]);

                $this->info("Password rehashed for user: {$user->user_id}");
        }

        $this->info('All user passwords have been rehashed.');
    }
}
