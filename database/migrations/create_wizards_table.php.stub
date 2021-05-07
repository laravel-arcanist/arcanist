<?php declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWizardsTable extends Migration
{
    public function up(): void
    {
        Schema::create('wizards', function (Blueprint $table) {
            $table->increments('id');
            $table->string('class');
            $table->json('data');
            $table->timestamps();
        });
    }
}
