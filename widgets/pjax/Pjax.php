<?php


namespace core\widgets\pjax;


use core\base\App;
use core\base\Response;
use core\components\Widget;
use core\helpers\ArrayHelper;
use core\helpers\Json;
use core\web\Html;

class Pjax extends Widget
{
    /**
     * @var array the HTML attributes for the widget container tag. The following special options are recognized:
     *
     * - `tag`: string, the tag name for the container. Defaults to `div`
     */
    public $options = [];
    /**
     * @var string|false the jQuery selector of the links that should trigger pjax requests.
     * If not set, all links within the enclosed content of Pjax will trigger pjax requests.
     * If set to false, no code will be registered to handle links.
     * Note that if the response to the pjax request is a full page, a normal request will be sent again.
     */
    public $linkSelector;
    /**
     * @var string|false the jQuery selector of the forms whose submissions should trigger pjax requests.
     * If not set, all forms with `data-pjax` attribute within the enclosed content of Pjax will trigger pjax requests.
     * If set to false, no code will be registered to handle forms.
     * Note that if the response to the pjax request is a full page, a normal request will be sent again.
     */
    public $formSelector;
    /**
     * @var string The jQuery event that will trigger form handler. Defaults to "submit".
     */
    public $submitEvent = 'submit';
    /**
     * @var bool whether to enable push state.
     */
    public $enablePushState = true;
    /**
     * @var bool whether to enable replace state.
     */
    public $enableReplaceState = false;
    /**
     * @var int pjax timeout setting (in milliseconds). This timeout is used when making AJAX requests.
     * Use a bigger number if your server is slow. If the server does not respond within the timeout,
     * a full page load will be triggered.
     */
    public $timeout = 1000;
    /**
     * @var bool|int how to scroll the page when pjax response is received. If false, no page scroll will be made.
     * Use a number if you want to scroll to a particular place.
     */
    public $scrollTo = false;
    /**
     * @var array additional options to be passed to the pjax JS plugin.
     */
    public $clientOptions;
    /**
     * @inheritdoc
     * @internal
     */
    public static $counter = 0;
    /**
     * @inheritdoc
     */
    public static $autoIdPrefix = 'p';

    public function init(){
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
        if ($this->requiresPjax()) {
            ob_start();
            ob_implicit_flush(false);
            $view = $this->getView();
            $view->clear();
            if ($view->title !== null) {
                echo Html::tag('title', Html::encode($view->title));
            }
        } else {
            $options = $this->options;
            $tag = ArrayHelper::remove($options, 'tag', 'div');
            echo Html::startTag($tag, array_merge([
                'data-pjax-container' => '',
                'data-pjax-push-state' => $this->enablePushState,
                'data-pjax-replace-state' => $this->enableReplaceState,
                'data-pjax-timeout' => $this->timeout,
                'data-pjax-scrollto' => $this->scrollTo,
            ], $options));
        }
    }


    public function run()
    {
        if (!$this->requiresPjax()) {
            echo Html::endTag(ArrayHelper::remove($this->options, 'tag', 'div'));
            $this->registerClientScript();
            return;
        }
        $content = ob_get_clean();
        // only need the content enclosed within this widget
        $response = App::$instance->getResponse();
        $response->clearOutputBuffers();
        $response->setStatusCode(200);
        $response->format = Response::FORMAT_HTML;
        $response->content = $content;
        $response->headers->setDefault('X-Pjax-Url', App::$instance->request->url);
        $response->send();
    }

    private function requiresPjax()
    {
        $headers = App::$instance->getRequest()->getHeaders();
        return $headers->get('X-Pjax') && explode(' ', $headers->get('X-Pjax-Container'))[0] === '#' . $this->options['id'];
    }

    private function registerClientScript()
    {
        $id = $this->options['id'];
        $this->clientOptions['push'] = $this->enablePushState;
        $this->clientOptions['replace'] = $this->enableReplaceState;
        $this->clientOptions['timeout'] = $this->timeout;
        $this->clientOptions['scrollTo'] = $this->scrollTo;
        if (!isset($this->clientOptions['container'])) {
            $this->clientOptions['container'] = "#$id";
        }
        $options = Json::htmlEncode($this->clientOptions);
        $js = '';
        if ($this->linkSelector !== false) {
            $linkSelector = Json::htmlEncode($this->linkSelector !== null ? $this->linkSelector : '#' . $id . ' a');
            $js .= "jQuery(document).pjax($linkSelector, $options);";
        }
        if ($this->formSelector !== false) {
            $formSelector = Json::htmlEncode($this->formSelector !== null ? $this->formSelector : '#' . $id . ' form[data-pjax]');
            $submitEvent = Json::htmlEncode($this->submitEvent);
            $js .= "\njQuery(document).on($submitEvent, $formSelector, function (event) {jQuery.pjax.submit(event, $options);});";
        }
        $view = $this->getView();
        PjaxAssets::register();
        if ($js !== '') {
            $view->registerJs($js);
        }
    }
}