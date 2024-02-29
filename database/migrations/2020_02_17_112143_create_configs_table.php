<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateConfigsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if ( !Schema::hasTable('configs') ) {
			Schema::create('configs', function(Blueprint $table)
			{
				$table->increments('id');
				$table->boolean('payu_enable')->nullable();
				$table->boolean('instamojo_enable')->nullable();
				$table->boolean('stripe_enable')->nullable();
				$table->boolean('paypal_enable')->nullable();
				$table->boolean('fb_login_enable')->nullable();
				$table->boolean('google_login_enable')->nullable();
				$table->integer('pincode_system')->default(0);
				$table->timestamps();
				$table->integer('paytm_enable')->unsigned()->default(0);
				$table->integer('razorpay')->unsigned()->default(0);
				$table->integer('payhere_enable')->unsigned()->default(0);
				$table->integer('braintree_enable')->unsigned()->default(0);
				$table->integer('paystack_enable')->unsigned()->default(0);
				$table->integer('amazon_enable')->unsigned()->default(0);
				$table->integer('linkedin_enable')->unsigned()->default(0);
				$table->integer('twitter_enable')->unsigned()->default(0);
				$table->integer('cashfree_enable')->unsigned()->default(0);
				$table->integer('skrill_enable')->unsigned()->default(0);
				$table->integer('rave_enable')->unsigned()->default(0);
				$table->integer('moli_enable')->unsigned()->default(0);
				$table->integer('omise_enable')->unsigned()->default(0);
				$table->integer('sslcommerze_enable')->unsigned()->default(0);
				$table->integer('iyzico_enable')->unsigned()->default(0);
				$table->integer('msg91_enable')->unsigned()->default(0);
				$table->integer('sms_channel')->unsigned()->default(0);
				$table->integer('enable_amarpay')->unsigned()->default(0);
			});
		}
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('configs');
	}

}
