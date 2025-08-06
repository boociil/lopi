<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('siakip2_url', function (Blueprint $table) {
            $table->string('id',36)->primary();
            $table->string('nama', 256)->nullable();
            $table->string('file_param', 1024)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';
        });

        Schema::create('siakip2_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 100)->unique();
            $table->string('email', 100)->unique()->nullable();
            $table->string('nip_baru', 30)->unique()->nullable();
            $table->string('nip_lama', 10)->unique()->nullable();
            $table->string('nama', 100);
            $table->string('password');
            $table->string('jabatan', 255)->nullable();
            $table->string('golonagan', 255)->nullable();
            $table->string('is_madya', 1)->default('0');
            $table->string('photo_url_id', 36)->nullable();
            $table->string('bps_photo_url', 255)->nullable();
            $table->tinyInteger('active')->unsigned()->default(1);// 0: tidak active 1:active
            $table->rememberToken();
            $table->timestamps();

            $table->engine = 'InnoDB';

            $table->foreign('photo_url_id', '2_users_fk_photo_url_id')->references('id')->on('siakip2_url');
        });

        Schema::create('siakip2_satuan_kerja', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('kode', 10);
            $table->string('nama', 255);
            $table->tinyInteger('active')->unsigned()->default(1);// 0: tidak active 1:active
            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';
        });

        Schema::create('siakip2_satuan_kerja_tahun', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('nama', 255);
            $table->smallInteger('tahun')->unsigned();
            $table->string('satuan_kerja_id', 36);
            $table->string('kode_satuan_kerja', 10);
            $table->string('type', 4); // prov, kab
            $table->string('parent_id', 36)->nullable(); // jika kab maka apa prov nya
            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';

            $table->foreign('satuan_kerja_id', '2_satuan_kerja_tahun_fk_satuan_kerja_id')->references('id')->on('siakip2_satuan_kerja');
        });

        Schema::create('siakip2_fungsi', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('nama', 255);

            $table->string('sk_url_id', 36)->nullable();
            $table->string('photo_url_id', 36)->nullable();

            $table->string('satuan_kerja_tahun_id', 36);
            $table->smallInteger('tahun')->unsigned();

            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';

            $table->foreign('sk_url_id', '2_fungsi_fk_sk_url_id')->references('id')->on('siakip2_url');
            $table->foreign('photo_url_id', '2_fungsi_fk_photo_url_id')->references('id')->on('siakip2_url');
            $table->foreign('satuan_kerja_tahun_id', '2_fungsi_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
        });

        Schema::create('siakip2_tim', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('nama', 255);

            $table->string('sk_url_id', 36)->nullable();
            $table->string('photo_url_id', 36)->nullable();

            $table->string('satuan_kerja_tahun_id', 36);
            $table->string('fungsi_id', 36)->nullable();
            $table->smallInteger('tahun')->unsigned();
            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';

            $table->foreign('sk_url_id', '2_tim_fk_sk_url_id')->references('id')->on('siakip2_url');
            $table->foreign('photo_url_id', '2_tim_fk_photo_url_id')->references('id')->on('siakip2_url');
            $table->foreign('satuan_kerja_tahun_id', '2_tim_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
            $table->foreign('fungsi_id', '2_tim_fk_fungsi_id')->references('id')->on('siakip2_fungsi');
        });

        Schema::create('siakip2_peran', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('tipe_peran', 30);// 0: global, 1:satuan_kerja_tahun, 2:tim, 3:halama, 4:sub_halaman, 5:isi
            $table->string('parent_id', 36)->nullable();

            $table->string('pemilik_username', 100)->nullable();
            $table->string('pemilik_tim_id', 36)->nullable();
            $table->string('pemilik_satuan_kerja_tahun_id', 36)->nullable();
            $table->smallInteger('tahun')->unsigned()->nullable();

            $table->string('peran', 100);
            // peran => [anggota, admin, pimpinan]

            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';

            $table->foreign('pemilik_username', '2_peran_fk_pemilik_username')->references('username')->on('siakip2_users');
            $table->foreign('pemilik_tim_id', '2_peran_fk_pemilik_tim_id')->references('id')->on('siakip2_tim');
            $table->foreign('pemilik_satuan_kerja_tahun_id', '2_peran_fk_pemilik_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
        });

        Schema::create('siakip2_tag', function (Blueprint $table) {
            $table->string('id',36)->primary();
            $table->string('tag_id', 36);

            $table->string('nama')->nullable();
            $table->string('warna', 36)->nullable();

            $table->string('pemilik_id', 36);
            $table->smallInteger('tahun')->unsigned();

            $table->timestamps();
            $table->softDeletes();
            $table->engine = 'InnoDB';
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('siakip2_item_page');
        Schema::dropIfExists('siakip2_page');
        Schema::dropIfExists('siakip2_tag_have');
        Schema::dropIfExists('siakip2_tag');
        Schema::dropIfExists('siakip2_user_tim_peran');
        Schema::dropIfExists('siakip2_tim');
        Schema::dropIfExists('siakip2_satuan_kerja_tahun');
        Schema::dropIfExists('siakip2_satuan_kerja');
        Schema::dropIfExists('siakip2_tahun');
        Schema::dropIfExists('siakip2_user_prop');
        Schema::dropIfExists('siakip2_users');
        Schema::dropIfExists('siakip2_url');
    }
}
