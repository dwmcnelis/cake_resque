<?php
namespace CakeResque;

/**
 * CakeResque Class
 *
 * Proxy to Resque, enabling logging function
 */
class CakeResque
{

	/**
	 * Resque classname.
	 *
	 * @var string
	 */
	public static $resqueClass = 'Resque\Resque';

	/**
	 * Redis instance.
	 *
	 * @var Predis\Client
	 */
	public static $redis = null;

	/**
	 * Logger instance.
	 *
	 * @var Resque\Logger
	 */
	public static $logger = null;

	/**
	 * Resque instance.
	 *
	 * @var Resque\Resque
	 */
	public static $resque = null;

	/**
	 * Initialization.
	 *
	 * It loads the required classes for web and cli environments.
	 *
	 * @param array $config Configuration options.
	 * @throws ConfigureException if needed configuration parameters are not found.
	 * @return void
	 */
	public static function init($config = null)
	{
		$config = self::loadConfig($config);

		// if (!($redis = Configure::read('CakeResque.Redis'))) {
		// 		throw new ConfigureException(__d('cake_resque', 'There is an error in the configuration file.'));
		// }

		$redis = $config['Redis'];

		if ((empty($redis['url'])) &&
				(empty($redis['scheme']) && empty($redis['host']) && empty($redis['port'])) &&
				(empty($redis['scheme']) && empty($redis['path'])) &&
				(empty($redis['scheme']) && empty($redis['ssl']))) {
			//throw new ConfigureException(__d('cake_resque', 'There is an error in the Redis configuration key.'));
			throw new Exception('There is an error in the Redis configuration key.');
		}

		self::$redis = new \Predis\Client($redis);

		self::$logger = new \Resque\Logger();
		self::$logger->ansi(true);
		self::$logger->extremely_verbose();

		self::$resque = new \Resque\Resque(self::$redis);
		self::$resque->setLogger(self::$logger);
	}

	/**
	 * Load configuration.
	 *
	 * If 'CakeResque' configuration key is not set, the default configuration is loaded.
	 *
	 * @param array $config Configuration options.
	 * @return void
	 */
	public static function loadConfig($config = null)
	{
		return array(
			'Redis' => array(
				'scheme' => 'tcp',
				'host' => '127.0.0.1',
				'port' => 6379
			)
			// 'Redis' => array(
			// 	'url' => 'tcp://127.0.0.1:6379'
			// )
			// 'Redis' => array(
			// 	'scheme' => 'unix',
			// 	'path' => '/path/to/redis.sock'
			// )
			// 'Redis' => array(
			// 	'url' => 'unix:/path/to/redis.sock'
			// )
			// 'Redis' => array(
			// 	'scheme' => 'tls',
			// 	'ssl' => array(
			// 		'cafile' => 'private.pem',
			// 		'verify_peer' => true
			// 	)
			// )
			// 'Redis' => array(
			// 	'url' => 'tls://127.0.0.1?ssl[cafile]=private.pem&ssl[verify_peer]=1'
			// )
		);

		// if ($config !== null) {
		// 	Configure::write('CakeResque', $config);
		// }

		// if (($hasCheck = method_exists('Configure', 'check')) && !Configure::check('CakeResque') ||
		// 		!$hasCheck && !self::checkConfig('CakeResque')) {
		// 	Configure::load('CakeResque.config');
		// }
	}

	public static function getRedis()
	{
		return self::$redis;
	}

	public static function getLogger()
	{
		return self::$logger;
	}

	public static function getResque()
	{
		return self::$resque;
	}

	/**
	 * Enqueue a Job and keep a log for debugging.
	 *
	 * @param string $queue Name of the queue to enqueue the job to.
	 * @param string $class Class of the job.
	 * @param array $args Arguments passed to the job.
	 * @param boolean $trackStatus Whether to track the status of the job.
	 * @return string Job Id.
	 */
	public static function enqueue($queue, $class, $args = [], $track = null)
	{
		if ($track === null) {
			//$track = Configure::read('CakeResque.Job.track');
		}

		if (!is_array($args)) {
			$args = [$args];
		}

		//$id = call_user_func_array(self::$resqueClass . '::enqueue', array_merge([$queue], [$class], [$args], [$track]));

		$id = self::$resque->enqueue($queue, $class, $args, $track);

		if (defined('DEBUG_BACKTRACE_IGNORE_ARGS')) {
			$caller = version_compare(PHP_VERSION, '5.4.0') >= 0
				? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)
				: debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		} else {
			$caller = debug_backtrace();
		}

		self::$logger->debug('queue: {queue}, class: {class}, args: {args}, jobId: {jobId}, caller: {caller}', array(
			'queue'  => $queue,
			'class'  => $class,
			'args'   => json_encode($args),
			'jobId'  => $id,
			'caller' => json_encode($caller)
		));

		return $id;
	}

}
