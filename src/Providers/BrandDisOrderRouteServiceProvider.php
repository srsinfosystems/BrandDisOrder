<?php
namespace BrandDisOrder\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;

/**
 * Class StockUpdatePluginRouteServiceProvider
 * @package StockUpdatePlugin\Providers
 */
class BrandDisOrderRouteServiceProvider extends RouteServiceProvider
{
	/**
	 * @param Router $router
	 */
	public function map(Router $router)
	{
		$router->get('save_order', 'BrandDisOrder\Controllers\ContentController@runOrder');

	}


}
