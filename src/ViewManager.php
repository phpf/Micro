<?php

namespace Phpf;

use SplObserver;
use SplSubject;
use RuntimeException;
use Phpf\Common\DataContainer;
use Phpf\Common\iEventable;
use Phpf\Common\iManager;
use Phpf\View\Parser\Php as PhpParser;
use Phpf\View\Parser\AbstractParser;
use Phpf\View\Assets;
use Phpf\View\View;
use Phpf\View\Part;
use Phpf\Filesystem;
use Phpf\EventContainer;

class ViewManager extends DataContainer implements iEventable, iManager, SplObserver
{
	
	/**
	 * @var Phpf\Filesystem
	 */
	protected $filesystem;

	/**
	 * @var Phpf\View\AbstractParser
	 */
	protected $events;

	/**
	 * @var Phpf\View\Assets
	 */
	protected $assets;
	
	/**
	 * @var array
	 */
	protected $parsers = array();

	/**
	 * @var array
	 */
	protected $views = array();

	/**
	 * Construct manager with Filesystem
	 */
	public function __construct(Filesystem &$filesystem) {

		$this->filesystem = &$filesystem;
		
		$this->addParser(new PhpParser);
	}
	
	/**
	 * [SplObserver]
	 * 
	 * Called when a view is being rendered.
	 * 
	 * @param View $view The view being rendered
	 * @return void
	 */
	public function update(SplSubject $view) {
		
		if (isset($this->events)) {
			$this->trigger('view.render', $view);
		}
	}

	/**
	 * Sets assets object if not set
	 */
	public function useAssets(Assets $assets = null) {
		if (! isset($this->assets)) {
			$this->assets = isset($assets) ? $assets : new Assets();
		}
		return $this;
	}

	/**
	 * Returns Assets object
	 */
	public function getAssets() {
		return $this->assets;
	}
	
	/**
	 * Returns true if assets set
	 */
	public function usingAssets() {
		return isset($this->assets);
	}
	
	/**
	 * Sets the events container as a property.
	 */
	public function setEvents(EventContainer $container) {
		$this->events = $container;
		return $this;
	}

	/**
	 * Whether events library is available.
	 */
	public function eventsAvailable() {
		return isset($this->events);
	}

	/**
	 * Adds a callback to perform on action.
	 */
	public function on($action, $call, $priority = 10) {

		if (! isset($this->events)) {
			throw new RuntimeException("Cannot bind event without EventContainer.");
		}
		
		$this->events->on($action, $call, $priority);
		
		return $this;
	}

	/**
	 * Triggers action callbacks.
	 */
	public function trigger($action, $view = null) {

		if (! isset($this->events)) {
			throw new RuntimeException("Cannot trigger event without EventContainer.");
		}
		
		return $this->events->trigger($action, $view, $this);
	}
	
	/**
	 * Returns the last returned view, if any.
	 */
	public function getCurrentView() {
		return empty($this->views)
			? null
			: reset($this->views);
	}

	/**
	 * Find and return a View.
	 */
	public function getView($view, $type = 'php') {
		
		$view .= '.' . $type;
		
		if (isset($this->views[$view])) {
			return $this->views[$view];
		}
		
		if (! $parser = $this->getParser($type)) {
			throw new RuntimeException("No parser for view type $type.");
		}
		
		if (! $file = $this->filesystem->locate($view, 'views')) {
			return null;
		}
		
		return $this->views[$view] = new View($file, $parser, $this, $this->getData());
	}

	/**
	 * Find and return a view part.
	 */
	public function getPart($name, $type = 'php') {

		if (! $parser = $this->getParser($type)) {
			throw new RuntimeException("No parser for view type $type.");
		}
		
		if (! $file = $this->filesystem->locate($name.'.'.$type, 'view-parts')) {
			return null;
		}

		return new Part($file, $parser, $this->getData());
	}
	
	/**
	 * Add a view parser
	 */
	public function addParser(AbstractParser $parser) {
		$this->parsers[$parser->getType()] = $parser;
		return $this;
	}

	/**
	 * Get a registered parser for given type.
	 */
	public function getParser($type) {
		return isset($this->parsers[$type]) ? $this->parsers[$type] : null;
	}

	/**
	 * Implement iManager
	 * Manages 'views'
	 */
	final public function manages() {
		return 'views';
	}
	
}
