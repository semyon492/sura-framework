<?php
declare(strict_types=1);

namespace Sura\Libs;


use Closure;
use SplFileInfo;
use Sura\Exception\FileException;

/**
 * Class File
 * @package Sura\Libs
 * @deprecated
 */
class File extends SplFileInfo
{
	/**
	 * hash
	 * @var array
	 */
	protected array $hash = [];
	
	/** @var $hashName */
	protected mixed $hashName;
	
	/**
	 * File constructor.
	 * @param string $path
	 * @param bool $checkPath
	 */
	public function __construct(string $path, bool $checkPath = true)
	{
		if ($checkPath && !is_file($path)) {
			throw new FileException(sprintf('The file "%s" does not exist', $path));
		}
		
		parent::__construct($path);
	}
	
	/**
	 * @access public
	 * @param string $type
	 * @return string
	 */
	public function hash(string $type = 'sha1'): string
	{
		if (!isset($this->hash[$type])) {
			$this->hash[$type] = hash_file($type, $this->getPathname());
		}
		
		return $this->hash[$type];
	}
	
	/**
	 * MD5
	 * @access public
	 * @return string
	 */
	public function md5(): string
	{
		return $this->hash('md5');
	}
	
	/**
	 * SHA1
	 * @access public
	 * @return string
	 */
	public function sha1(): string
	{
		//'sha1'
		return $this->hash();
	}
	
	/**
	 * getMime
	 * @access public
	 * @return string
	 */
	public function getMime(): string
	{
		$f_info = finfo_open(FILEINFO_MIME_TYPE);
		
		return finfo_file($f_info, $this->getPathname());
	}
	
	/**
	 * 移动文件
	 * @access public
	 * @param string $directory 保存路径
	 * @param string|null $name 保存的文件名
	 * @return File
	 */
	public function move(string $directory, string $name = null): File
	{
		$target = $this->getTargetFile($directory, $name);
		
		set_error_handler(function ($msg) use (&$error) {
			$error = $msg;
		});
		$renamed = rename($this->getPathname(), (string)$target);
		restore_error_handler();
		if (!$renamed) {
			throw new FileException(sprintf('Could not move the file "%s" to "%s" (%s)', $this->getPathname(), $target, strip_tags($error)));
		}
		
		@chmod((string)$target, 0666 & ~umask());
		
		return $target;
	}
	
	/**
	 * 实例化一个新文件
	 * @param string $directory
	 * @param null|string $name
	 * @return File
	 */
	protected function getTargetFile(string $directory, string $name = null): File
	{
		if (!is_dir($directory)) {
			if (false === @mkdir($directory, 0777, true) && !is_dir($directory)) {
				throw new FileException(sprintf('Unable to create the "%s" directory', $directory));
			}
		} elseif (!is_writable($directory)) {
			throw new FileException(sprintf('Unable to write in the "%s" directory', $directory));
		}
		
		$target = rtrim($directory, '/\\') . '/' . (null === $name ? $this->getBasename() : $this->getName($name));
		
		return new self($target, false);
	}
	
	/**
	 * 获取文件名
	 * @param string $name
	 * @return string
	 */
	protected function getName(string $name): string
	{
		$originalName = str_replace('\\', '/', $name);
		$pos = strrpos($originalName, '/');
		$originalName = false === $pos ? $originalName : substr($originalName, $pos + 1);
		
		return $originalName;
	}
	
	/**
	 * 文件扩展名
	 * @return string
	 */
	public function extension(): string
	{
		return $this->getExtension();
	}
	
	/**
	 * 自动生成文件名
	 * @access public
	 * @param string|Closure $rule
	 * @return string
	 */
	public function hashName($rule = 'date'): string
	{
		if (!$this->hashName) {
			if ($rule instanceof Closure) {
				$this->hashName = call_user_func_array($rule, [$this]);
			} else {
				switch (true) {
					case in_array($rule, hash_algos()):
						$hash = $this->hash($rule);
						$this->hashName = substr($hash, 0, 2) . DIRECTORY_SEPARATOR . substr($hash, 2);
						break;
					case is_callable($rule):
						$this->hashName = call_user_func($rule);
						break;
					default:
						$this->hashName = date('Ymd') . DIRECTORY_SEPARATOR . md5((string)microtime(true));
						break;
				}
			}
		}
		
		return $this->hashName . '.' . $this->extension();
	}
}