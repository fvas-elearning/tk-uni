<?php
namespace Uni\Controller;

use Dom\Loader;
use StdClass;
use Tk\Alert;
use Tk\Log;
use Tk\Plugin\Factory;
use Tk\Request;
use Dom\Template;
use Tk\Table;
use Tk\Table\Cell\ButtonCollection;
use Tk\Table\Cell\Date;
use Tk\Table\Cell\Text;
use Tk\Table\Ui\ActionButton;
use Uni\Uri;
use Uni\Config;

/**
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class PluginZoneManager extends AdminIface
{

    /**
     * @var Table
     */
    protected $table = null;

    /**
     * @var string
     */
    protected $zoneName = '';

    /**
     * @var int
     */
    protected $zoneId = 0;


    /**
     * @var Factory
     */
    protected $pluginFactory = null;

    /**
     *
     */
    public function __construct()
    {
        $this->setPageTitle('Plugin Manager');
    }

    /**
     * @param Request $request
     * @param string $zoneName
     * @param string $zoneId
     * @throws \Exception
     */
    public function doDefault(Request $request, $zoneName = '', $zoneId = '')
    {
        if (!$this->zoneName)
            $this->zoneName = $zoneName;
        if (!$this->zoneId)
            $this->zoneId = $zoneId;

        if (!$this->zoneName || !$this->zoneId) {
            throw new \Tk\Exception('Invalid zone plugin information?');
        }

        if ($request->has('enable')) {
            $this->doEnablePlugin($request);
        } else if ($request->has('disable')) {
            $this->doDisablePlugin($request);
        }


        $this->setPageTitle($this->makeTitleFromZone($this->zoneName) . ' Plugin Manager');
        $this->pluginFactory = Config::getInstance()->getPluginFactory();

        // Plugin manager table
        $this->table = Config::getInstance()->createTable('PluginList');
        $this->table->setRenderer(Config::getInstance()->createTableRenderer($this->table));

        $this->table->appendCell(new \Tk\Table\Cell\Text('icon'))->setLabel('')
            ->addOnCellHtml(function ($cell, $obj, $html) {
                $config = Config::getInstance();
                $pluginName = $config->getPluginFactory()->cleanPluginName($obj->name);
                if (is_file($config->getPluginPath().'/'.$pluginName.'/icon.png')) {
                    $url =  $config->getPluginUrl() . '/' . $pluginName . '/icon.png';
                    $html = sprintf('<img class="media-object" src="%s" var="icon" style="width: 32px;" choice="icon" />', $url);
                }
                return $html;
            });;


        $zoneName = $this->zoneName;
        $zoneId = $this->zoneId;
        $actionsCell = ButtonCollection::create('actions')->setAttr('style', 'width: 55px;');
        $actionsCell->append(ActionButton::createBtn('Enable Plugin ', '#', 'fa fa-sign-in'))
            ->addCss('btn-success btn-xs noblock act')
            ->setAttr('data-confirm', 'Are you sure you want to enable this plugin?')
            ->addOnShow(function (ButtonCollection $cell, $obj, ActionButton $button) use ($zoneName, $zoneId) {
                /* @var $obj stdClass */
                $pluginFactory = Config::getInstance()->getPluginFactory();
                $pluginName = $pluginFactory->cleanPluginName($obj->name);
                /** @var \Tk\Plugin\Iface $plugin */
                $plugin = $pluginFactory->getPlugin($pluginName);
                if ($plugin->isZonePluginEnabled($zoneName, $zoneId)) {
                    $cell->getRow()->addCss('plugin-active');
                    $button->setVisible(false);
                } else {
                    $cell->getRow()->addCss('plugin-inactive');
                    $button->setUrl(Uri::create()->set('enable', $plugin->getName()));
                }
            })->setGroup('group');
        $actionsCell->append(ActionButton::createBtn('Configure Plugin ', '#', 'fa fa-cog'))
            ->addCss('btn-primary btn-xs noblock setup')
            ->addOnShow(function (ButtonCollection $cell, $obj, ActionButton $button) use ($zoneName, $zoneId) {
                /* @var $obj stdClass */
                $pluginFactory = Config::getInstance()->getPluginFactory();
                $pluginName = $pluginFactory->cleanPluginName($obj->name);
                /** @var \Tk\Plugin\Iface $plugin */
                $plugin = $pluginFactory->getPlugin($pluginName);
                if ($plugin->isZonePluginEnabled($zoneName, $zoneId)) {
                    $cell->getRow()->addCss('plugin-active');

                    if ($plugin->getZoneSettingsUrl($zoneName, $zoneId)) {
                        $button->setUrl($plugin->getZoneSettingsUrl($zoneName, $zoneId)->set('zoneId', $zoneId));
                    } else {
                        $button->setUrl(null);
                        $button->setAttr('disabled');
                        $button->addCss('disabled');
                    }
                } else {
                    $cell->getRow()->addCss('plugin-inactive');
                    $button->setVisible(false);
                }
            })->setGroup('group');
        $actionsCell->append(ActionButton::createBtn('Disable Plugin ', '#', 'fa fa-remove'))
            ->addCss('btn-danger btn-xs noblock deact')
            ->setAttr('data-confirm', 'Are you sure you want to disable this plugin?')
            ->addOnShow(function (ButtonCollection $cell, $obj, ActionButton $button) use ($zoneName, $zoneId) {
                /* @var $obj stdClass */
                $pluginFactory = Config::getInstance()->getPluginFactory();
                $pluginName = $pluginFactory->cleanPluginName($obj->name);
                /** @var \Tk\Plugin\Iface $plugin */
                $plugin = $pluginFactory->getPlugin($pluginName);
                if ($plugin->isZonePluginEnabled($zoneName, $zoneId)) {
                    $cell->getRow()->addCss('plugin-active');
                    $button->setUrl(Uri::create()->set('disable', $plugin->getName()));
                } else {
                    $cell->getRow()->addCss('plugin-inactive');
                    $button->setVisible(false);
                }
            })->setGroup('group');

        $this->table->appendCell($actionsCell)->addOnCellHtml(function (\Tk\Table\Cell\Iface $cell, $obj, $html) {
            /** @var $obj stdClass */
            $template = $cell->getTable()->getRenderer()->getTemplate();
            $css = <<<CSS
#PluginList .plugin-inactive td {
  opacity: 0.5;
}
#PluginList .plugin-inactive td.mActions {
  opacity: 1;  
}
.table > tbody > tr > td {
  vertical-align: middle;
}
CSS;
            $template->appendCss($css);
            return $html;
        });

        $this->table->appendCell(new Text('name'))->addCss('key')->setOrderProperty('');
        $this->table->appendCell(new Text('version'))->setOrderProperty('');
        $this->table->appendCell(new Date('time'))->setLabel('Created')->setOrderProperty('');
        
        $this->table->setList($this->getPluginList());

    }

    /**
     * @return array
     */
    private function getPluginList()
    {
        try {
            $pluginFactory = Config::getInstance()->getPluginFactory();
        } catch (\Exception $e) { Log::error($e->__toString()); }
        $plugins = $pluginFactory->getZonePluginList($this->zoneName);
        $list = array();
        /** @var \Tk\Plugin\Iface $plugin */
        foreach ($plugins as $plugin) {
            $info = $plugin->getInfo();
            $info->name = str_replace('ttek-plg/', '', $info->name);
            $list[] = $info;
        }
        return $list;
    }

    protected function doEnablePlugin(Request $request)
    {
        $pluginName = strip_tags(trim($request->get('enable')));
        if (!$pluginName) {
            Alert::addWarning('Cannot locate Plugin: ' . $pluginName);
            return;
        }
        try {
            Config::getInstance()->getPluginFactory()->enableZonePlugin($pluginName, $this->zoneName, $this->zoneId);
            Alert::addSuccess('Plugin `' . $pluginName . '` enabled.');
        }catch (\Exception $e) {
            Alert::addError('Plugin `' . $pluginName . '` cannot be enabled.');
        }
        Uri::create()->remove('enable')->redirect();
    }

    protected function doDisablePlugin(Request $request)
    {
        $pluginName = strip_tags(trim($request->get('disable')));
        if (!$pluginName) {
            Alert::addWarning('Cannot locate Plugin: ' . $pluginName);
            return;
        }
        try {
            Config::getInstance()->getPluginFactory()->disableZonePlugin($pluginName, $this->zoneName, $this->zoneId);
        } catch (\Exception $e) {
            Alert::addError('Plugin `' . $pluginName . '` cannot be disabled.');
        }
        Alert::addSuccess('Plugin `' . $pluginName . '` disabled');
        Uri::create()->remove('disable')->redirect();
    }

    /**
     * @return Template
     */
    public function show()
    {
        $template = parent::show();

        // render Table
        $template->appendTemplate('table', $this->table->getRenderer()->show());

        $template->setAttr('table', 'data-panel-title', $this->makeTitleFromZone($this->zoneName) . ' Plugins');
        $template->insertText('zone', $this->makeTitleFromZone($this->zoneName));

        return $template;
    }

    /**
     * @param $str
     * @return string
     */
    protected function makeTitleFromZone($str)
    {
        $str = preg_replace('/[A-Z]/', ' $0', $str);
        $str = preg_replace('/[^a-z0-9]/i', ' ', $str);
        return ucwords($str);
    }


    /**
     * DomTemplate magic method
     *
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div>

  <div class="tk-panel" data-panel-title="Plugins" data-panel-icon="fa fa-compress" var="table"></div>
  
</div>
HTML;
        return Loader::load($xhtml);
    }

}
