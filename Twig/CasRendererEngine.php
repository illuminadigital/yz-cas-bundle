<?php
namespace Illumina\CasBundle\Twig;

class CasRendererEngine
{
    /**
     * @var \Twig_Environment
     */
    private $environment;
    
    /**
     * @var \Twig_Template
     */
    private $template;
    
   /**
     * The variable in {@link CasView} used as cache key.
     */
    const CACHE_KEY_VAR = 'cache_key';

    /**
     * @var array
     */
    protected $defaultThemes;

    /**
     * @var array
     */
    protected $themes = array();

    /**
     * @var array
     */
    protected $resources = array();

    /**
     * @var array
     */
    private $resourceHierarchyLevels = array();

    
    /**
     * Creates a new renderer engine.
     *
     * @param array $defaultThemes The default themes. The type of these
     *                             themes is open to the implementation.
     */
    public function __construct(array $defaultThemes = array('cas_plain_layout.html.twig'))
    {
        $this->defaultThemes = $defaultThemes;
    }

    public function setEnvironment(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setTheme(CasView $view, $themes)
    {
        $cacheKey = $view->vars[self::CACHE_KEY_VAR];

        // Do not cast, as casting turns objects into arrays of properties
        $this->themes[$cacheKey] = is_array($themes) ? $themes : array($themes);

        // Unset instead of resetting to an empty array, in order to allow
        // implementations (like TwigRendererEngine) to check whether $cacheKey
        // is set at all.
        unset($this->resources[$cacheKey]);
        unset($this->resourceHierarchyLevels[$cacheKey]);
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceForBlockName(CasView $view, $blockName)
    {
        $cacheKey = $view->vars[self::CACHE_KEY_VAR];

        if (!isset($this->resources[$cacheKey][$blockName])) {
            $this->loadResourceForBlockName($cacheKey, $view, $blockName);
        }

        return $this->resources[$cacheKey][$blockName];
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceForBlockNameHierarchy(CasView $view, array $blockNameHierarchy, $hierarchyLevel)
    {
        $cacheKey = $view->vars[self::CACHE_KEY_VAR];
        $blockName = $blockNameHierarchy[$hierarchyLevel];

        if (!isset($this->resources[$cacheKey][$blockName])) {
            $this->loadResourceForBlockNameHierarchy($cacheKey, $view, $blockNameHierarchy, $hierarchyLevel);
        }

        return $this->resources[$cacheKey][$blockName];
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceHierarchyLevel(CasView $view, array $blockNameHierarchy, $hierarchyLevel)
    {
        $cacheKey = $view->vars[self::CACHE_KEY_VAR];
        $blockName = $blockNameHierarchy[$hierarchyLevel];

        if (!isset($this->resources[$cacheKey][$blockName])) {
            $this->loadResourceForBlockNameHierarchy($cacheKey, $view, $blockNameHierarchy, $hierarchyLevel);
        }

        // If $block was previously rendered loaded with loadTemplateForBlock(), the template
        // is cached but the hierarchy level is not. In this case, we know that the  block
        // exists at this very hierarchy level, so we can just set it.
        if (!isset($this->resourceHierarchyLevels[$cacheKey][$blockName])) {
            $this->resourceHierarchyLevels[$cacheKey][$blockName] = $hierarchyLevel;
        }

        return $this->resourceHierarchyLevels[$cacheKey][$blockName];
    }
        
    /**
     * {@inheritdoc}
     */
    public function renderBlock(CasView $view, $resource, $blockName, array $variables = array())
    {
        $cacheKey = $view->vars[self::CACHE_KEY_VAR];

        $context = $this->environment->mergeGlobals($variables);

        ob_start();

        // By contract,This method can only be called after getting the resource
        // (which is passed to the method). Getting a resource for the first time
        // (with an empty cache) is guaranteed to invoke loadResourcesFromTheme(),
        // where the property $template is initialized.

        // We do not call renderBlock here to avoid too many nested level calls
        // (XDebug limits the level to 100 by default)
        $this->template->displayBlock($blockName, $context, $this->resources[$cacheKey]);

        return ob_get_clean();
    }

    /**
     * Loads the cache with the resource for a given block name.
     *
     * This implementation eagerly loads all blocks of the themes assigned to the given view
     * and all of its ancestors views. This is necessary, because Twig receives the
     * list of blocks later. At that point, all blocks must already be loaded, for the
     * case that the function "block()" is used in the Twig template.
     *
     * @see getResourceForBlock()
     *
     * @param string   $cacheKey  The cache key of the form view.
     * @param CasView $view      The form view for finding the applying themes.
     * @param string   $blockName The name of the block to load.
     *
     * @return Boolean True if the resource could be loaded, false otherwise.
     */
    protected function loadResourceForBlockName($cacheKey, CasView $view, $blockName)
    {
        // The caller guarantees that $this->resources[$cacheKey][$block] is
        // not set, but it doesn't have to check whether $this->resources[$cacheKey]
        // is set. If $this->resources[$cacheKey] is set, all themes for this
        // $cacheKey are already loaded (due to the eager population, see doc comment).
        if (isset($this->resources[$cacheKey])) {
            // As said in the previous, the caller guarantees that
            // $this->resources[$cacheKey][$block] is not set. Since the themes are
            // already loaded, it can only be a non-existing block.
            $this->resources[$cacheKey][$blockName] = false;

            return false;
        }

        // Recursively try to find the block in the themes assigned to $view,
        // then of its parent view, then of the parent view of the parent and so on.
        // When the root view is reached in this recursion, also the default
        // themes are taken into account.

        // Check each theme whether it contains the searched block
        if (isset($this->themes[$cacheKey])) {
            for ($i = count($this->themes[$cacheKey]) - 1; $i >= 0; --$i) {
                $this->loadResourcesFromTheme($cacheKey, $this->themes[$cacheKey][$i]);
                // CONTINUE LOADING (see doc comment)
            }
        }

        // Check the default themes once we reach the root view without success
        if (!$view->parent) {
            for ($i = count($this->defaultThemes) - 1; $i >= 0; --$i) {
                $this->loadResourcesFromTheme($cacheKey, $this->defaultThemes[$i]);
                // CONTINUE LOADING (see doc comment)
            }
        }

        // Proceed with the themes of the parent view
        if ($view->parent) {
            $parentCacheKey = $view->parent->vars[self::CACHE_KEY_VAR];

            if (!isset($this->resources[$parentCacheKey])) {
                $this->loadResourceForBlockName($parentCacheKey, $view->parent, $blockName);
            }

            // EAGER CACHE POPULATION (see doc comment)
            foreach ($this->resources[$parentCacheKey] as $nestedBlockName => $resource) {
                if (!isset($this->resources[$cacheKey][$nestedBlockName])) {
                    $this->resources[$cacheKey][$nestedBlockName] = $resource;
                }
            }
        }

        // Even though we loaded the themes, it can happen that none of them
        // contains the searched block
        if (!isset($this->resources[$cacheKey][$blockName])) {
            // Cache that we didn't find anything to speed up further accesses
            $this->resources[$cacheKey][$blockName] = false;
        }

        return false !== $this->resources[$cacheKey][$blockName];
    }

    /**
     * Loads the resources for all blocks in a theme.
     * 
     * @see \Symfony\Bridge\Twig\Form\TwigRendererEngine\loadResourcesFromTheme
     *
     * @param string $cacheKey The cache key for storing the resource.
     * @param mixed  $theme    The theme to load the block from. This parameter
     *                         is passed by reference, because it might be necessary
     *                         to initialize the theme first. Any changes made to
     *                         this variable will be kept and be available upon
     *                         further calls to this method using the same theme.
     */
    protected function loadResourcesFromTheme($cacheKey, &$theme)
    {
        if (!$theme instanceof \Twig_Template) {
            /* @var \Twig_Template $theme */
            $theme = $this->environment->loadTemplate($theme);
        }
    
        if (null === $this->template) {
            // Store the first \Twig_Template instance that we find so that
            // we can call displayBlock() later on. It doesn't matter *which*
            // template we use for that, since we pass the used blocks manually
            // anyway.
            $this->template = $theme;
        }
    
        // Use a separate variable for the inheritance traversal, because
        // theme is a reference and we don't want to change it.
        $currentTheme = $theme;
    
        // The do loop takes care of template inheritance.
        // Add blocks from all templates in the inheritance tree, but avoid
        // overriding blocks already set.
        do {
            foreach ($currentTheme->getBlocks() as $block => $blockData) {
            if (!isset($this->resources[$cacheKey][$block])) {
                // The resource given back is the key to the bucket that
                    // contains this block.
                    $this->resources[$cacheKey][$block] = $blockData;
                }
            }
        } while (false !== $currentTheme = $currentTheme->getParent(array()));
    }
    
    /**
     * Loads the cache with the resource for a specific level of a block hierarchy.
     *
     * @see getResourceForBlockHierarchy()
     *
     * @param string   $cacheKey           The cache key used for storing the
     *                                     resource.
     * @param CasView $view                The view for finding the applying
     *                                     themes.
     * @param array    $blockNameHierarchy The block hierarchy, with the most
     *                                     specific block name at the end.
     * @param integer  $hierarchyLevel     The level in the block hierarchy that
     *                                     should be loaded.
     *
     * @return Boolean True if the resource could be loaded, false otherwise.
     */
    private function loadResourceForBlockNameHierarchy($cacheKey, CasView $view, array $blockNameHierarchy, $hierarchyLevel)
    {
        $blockName = $blockNameHierarchy[$hierarchyLevel];

        // Try to find a template for that block
        if ($this->loadResourceForBlockName($cacheKey, $view, $blockName)) {
            // If loadTemplateForBlock() returns true, it was able to populate the
            // cache. The only missing thing is to set the hierarchy level at which
            // the template was found.
            $this->resourceHierarchyLevels[$cacheKey][$blockName] = $hierarchyLevel;

            return true;
        }

        if ($hierarchyLevel > 0) {
            $parentLevel = $hierarchyLevel - 1;
            $parentBlockName = $blockNameHierarchy[$parentLevel];

            // The next two if statements contain slightly duplicated code. This is by intention
            // and tries to avoid execution of unnecessary checks in order to increase performance.

            if (isset($this->resources[$cacheKey][$parentBlockName])) {
                // It may happen that the parent block is already loaded, but its level is not.
                // In this case, the parent block must have been loaded by loadResourceForBlock(),
                // which does not check the hierarchy of the block. Subsequently the block must have
                // been found directly on the parent level.
                if (!isset($this->resourceHierarchyLevels[$cacheKey][$parentBlockName])) {
                    $this->resourceHierarchyLevels[$cacheKey][$parentBlockName] = $parentLevel;
                }

                // Cache the shortcuts for further accesses
                $this->resources[$cacheKey][$blockName] = $this->resources[$cacheKey][$parentBlockName];
                $this->resourceHierarchyLevels[$cacheKey][$blockName] = $this->resourceHierarchyLevels[$cacheKey][$parentBlockName];

                return true;
            }

            if ($this->loadResourceForBlockNameHierarchy($cacheKey, $view, $blockNameHierarchy, $parentLevel)) {
                // Cache the shortcuts for further accesses
                $this->resources[$cacheKey][$blockName] = $this->resources[$cacheKey][$parentBlockName];
                $this->resourceHierarchyLevels[$cacheKey][$blockName] = $this->resourceHierarchyLevels[$cacheKey][$parentBlockName];

                return true;
            }
        }

        // Cache the result for further accesses
        $this->resources[$cacheKey][$blockName] = false;
        $this->resourceHierarchyLevels[$cacheKey][$blockName] = false;

        return false;
    }
}
