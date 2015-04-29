<?php 
namespace Banklink;

use Illuminate\Support\ServiceProvider;

class BanklinkServiceProvider extends ServiceProvider {

	protected $defer = true;
	
	public function register()
	{
		$this->app->bind('banklink', function($app, $parameters)
		{
			switch($parameters['bank_code']){
				case 'seb':
					$protocol = new Protocol\iPizza(
							@\Configuration::where('code', '=', 'seb/vk_snd_id')->first()->value,
							@\Configuration::where('code', '=', 'seb/vk_name')->first()->value,
							@\Configuration::where('code', '=', 'seb/vk_acc')->first()->value,
							@\Configuration::where('code', '=', 'seb/vk_privkey')->first()->value,
							@\Configuration::where('code', '=', 'seb/vk_pubkey')->first()->value,
							@$parameters['return_url'],
							$mbStrlen = true
					);
					return new SEB($protocol, $testMode = false, @\Configuration::where('code', '=', 'seb/vk_dest')->first()->value );
					
				case 'swedbank':
					$protocol = new Protocol\iPizza(
							@\Configuration::where('code', '=', 'swedbank/vk_snd_id')->first()->value,
							@\Configuration::where('code', '=', 'swedbank/vk_name')->first()->value,
							@\Configuration::where('code', '=', 'swedbank/vk_acc')->first()->value,
							@\Configuration::where('code', '=', 'swedbank/vk_privkey')->first()->value,
							@\Configuration::where('code', '=', 'swedbank/vk_pubkey')->first()->value,
							@$parameters['return_url'],
							$mbStrlen = true
					);
					return new Swedbank($protocol, $testMode = false, @\Configuration::where('code', '=', 'swedbank/vk_dest')->first()->value );
					
				case 'nordea':	
					$protocol = new Protocol\Solo(
							@\Configuration::where('code', '=', 'nordea/rcv_id')->first()->value,
							@\Configuration::where('code', '=', 'nordea/mac_key')->first()->value,
							@$parameters['return_url'],
							@\Configuration::where('code', '=', 'nordea/rcv_name')->first()->value,
							@\Configuration::where('code', '=', 'nordea/rcv_account')->first()->value
					);
					return new Nordea($protocol, $testMode = false, @\Configuration::where('code', '=', 'nordea/vk_dest')->first()->value );

                case 'lhv':
                    $protocol = new Protocol\iPizza(
                        @\Configuration::where('code', '=', 'lhv/vk_snd_id')->first()->value,
                        @\Configuration::where('code', '=', 'lhv/vk_name')->first()->value,
                        @\Configuration::where('code', '=', 'lhv/vk_acc')->first()->value,
                        @\Configuration::where('code', '=', 'lhv/vk_privkey')->first()->value,
                        @\Configuration::where('code', '=', 'lhv/vk_pubkey')->first()->value,
                        @$parameters['return_url'],
                        $mbStrlen = true
                    );
                    return new LHV($protocol, $testMode = false, @\Configuration::where('code', '=', 'lhv/vk_dest')->first()->value );

				case 'estcard':
					return new Estcard(	@\Configuration::where('code', '=', 'estcard/url')->first()->value, 
										@$parameters['return_url'],
										@\Configuration::where('code', '=', 'estcard/id')->first()->value
									);
					
			}
		});
	}
	
	public function provides()
	{
		return array('banklink');
	}
	
}