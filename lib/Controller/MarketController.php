<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Market\Controller;

use OCA\Market\MarketService;
use OCP\AppFramework\Controller;
use OCP\IRequest;

class MarketController extends Controller {

	/** @var MarketService  */
	private $marketService;

	public function __construct($appName, IRequest $request, MarketService $marketService) {
		parent::__construct($appName, $request);
		$this->marketService = $marketService;
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @return array|mixed
	 * @param $category
	 */
	public function appPerCategory($category) {
		return $this->queryData($category);
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @return array|mixed
	 */
	public function categories() {
		return $this->marketService->getCategories();
	}
	/**
	 * @NoCSRFRequired
	 *
	 * @return array|mixed
	 */
	public function index() {
		return $this->queryData();
	}

	/**
	 * @param string $appId
	 * @return array
	 */
	public function install($appId) {
		try {
			$this->marketService->installApp($appId);
			return [
				'error' => false,
				'message' => "App $appId installed successfully"
			];
		} catch(\Exception $ex) {
			return ['error' => true,
				'message' => $ex->getMessage()
			];
		}
	}

	/**
	 * @param string $appId
	 * @return array
	 */
	public function uninstall($appId) {
		try {
			$this->marketService->uninstallApp($appId);
			return [
				'error' => false,
				'message' => "App $appId uninstalled successfully"
			];
		} catch(\Exception $ex) {
			return ['error' => true,
				'message' => $ex->getMessage()
			];
		}
	}

	/**
	 * @param string $appId
	 * @return array
	 */
	public function update($appId) {
		try {
			$this->marketService->updateApp($appId);
			return [
				'error' => false,
				'message' => "App $appId updated successfully"
			];
		} catch(\Exception $ex) {
			return ['error' => true,
				'message' => $ex->getMessage()
			];
		}
	}

	/**
	 * @param string | null $category
	 * @return array
	 */
	protected function queryData($category = null) {
		$apps = $this->marketService->listApps($category);

		return array_map(function ($app) {
			$app['installed'] = $this->marketService->isAppInstalled($app['id']);
			$releases = array_map(function ($release) {
				$missing = $this->marketService->getMissingDependencies($release);
				$release['canInstall'] = empty($missing);
				$release['missingDependencies'] = $missing;
				return $release;
			}, $app['releases']);
			unset($app['releases']);
			if ($app['installed']) {
				$app['installInfo'] = $this->marketService->getInstalledAppInfo($app['id']);
				$app['updateInfo'] = $this->marketService->getAvailableUpdateVersion($app['id']);
				
				$filteredReleases = array_filter($releases, function ($release) use ($app) {
					if (empty($app['updateInfo'])) {
						return $release['version'] === $app['updateInfo'];
					}
					return $release['version'] === $app['updateInfo'];
				});
				$app['release'] = array_pop($filteredReleases);
			} else {
				$app['updateInfo'] = [];
				usort($releases, function ($a, $b) {
					return version_compare($a, $b, '>');
				});
				if (!empty($releases)) {
					$app['release'] = array_pop($releases);
				}
			}
			return $app;
		}, $apps);
	}
}
