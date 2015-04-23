<?php
App::uses('FormHelper', 'View/Helper');
App::uses('Set', 'Utility');

class BoostCakeFormHelper extends FormHelper {

  public $helpers = array('Html' => array('className' => 'BoostCake.BoostCakeHtml'));

  protected $_divOptions = array();

  protected $_inputOptions = array();

  protected $_inputType = null;

  protected $_fieldName = null;


  public function parentInput($fieldName, $options = array())
  {
    return parent::input($fieldName, $options);
  }


  /**
   * Overwrite FormHelper::input()
   * Generates a form input element complete with label and wrapper div
   *
   * ### Options
   *
   * See each field type method for more information. Any options that are part of
   * $attributes or $options for the different **type** methods can be included in `$options` for input().i
   * Additionally, any unknown keys that are not in the list below, or part of the selected type's options
   * will be treated as a regular html attribute for the generated input.
   *
   * - `type` - Force the type of widget you want. e.g. `type => 'select'`
   * - `label` - Either a string label, or an array of options for the label. See FormHelper::label().
   * - `div` - Either `false` to disable the div, or an array of options for the div.
   *  See HtmlHelper::div() for more options.
   * - `options` - For widgets that take options e.g. radio, select.
   * - `error` - Control the error message that is produced. Set to `false` to disable any kind of error reporting (field
   *    error and error messages).
   * - `errorMessage` - Boolean to control rendering error messages (field error will still occur).
   * - `empty` - String or boolean to enable empty select box options.
   * - `before` - Content to place before the label + input.
   * - `after` - Content to place after the label + input.
   * - `between` - Content to place between the label + input.
   * - `format` - Format template for element order. Any element that is not in the array, will not be in the output.
   *  - Default input format order: array('before', 'label', 'between', 'input', 'after', 'error')
   *  - Default checkbox format order: array('before', 'input', 'between', 'label', 'after', 'error')
   *  - Hidden input will not be formatted
   *  - Radio buttons cannot have the order of input and label elements controlled with these settings.
   *
   * Added options
   * - `wrapInput` - Either `false` to disable the div wrapping input, or an array of options for the div.
   *  See HtmlHelper::div() for more options.
   * - `checkboxDiv` - Wrap input checkbox tag's class.
   * - `beforeInput` - Content to place before the input.
   * - `afterInput` - Content to place after the input.
   * - `errorClass` - Wrap input tag's error message class.
   *
   * @param string $fieldName This should be "Modelname.fieldname"
   * @param array $options Each type of input takes different options.
   * @return string Completed form widget.
   * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#creating-form-elements
   */

  public function input($fieldName, $options = array())
  {
    $this->_fieldName = $fieldName;

    $default = array('error' => array('attributes' => array('wrap' => 'span',
                                                            'class' => 'help-block text-danger')),
                     'wrapInput' => array('tag' => 'div'),
                     'checkboxDiv' => 'checkbox',
                     'beforeInput' => '',
                     'afterInput' => '',
                     'errorClass' => 'has-error error');

    $options = Hash::merge($default, $this->_inputDefaults, $options);

    $this->_inputOptions = $options;

    $options['error'] = false;

    if (isset($options['wrapInput'])) {
      unset($options['wrapInput']);
    }
    if (isset($options['checkboxDiv'])) {
      unset($options['checkboxDiv']);
    }
    if (isset($options['beforeInput'])) {
      unset($options['beforeInput']);
    }
    if (isset($options['afterInput'])) {
      unset($options['afterInput']);
    }
    if (isset($options['errorClass'])) {
      unset($options['errorClass']);
    }

    $inputDefaults = $this->_inputDefaults;
    $this->_inputDefaults = array();

    if (!empty($options['help'])) {
      if (!isset($options['after'])) {
        $options['after'] = '';
      }
      $options['after'] .= '<span class="help-block">' . $options['help'] . '</span>';
    }

    $html = parent::input($fieldName, $options);

    $this->_inputDefaults = $inputDefaults;

    if ($this->_inputType === 'checkbox') {
      if (isset($options['before'])) {
        $html = str_replace($options['before'], '%before%', $html);
      }
      $regex = '/(<label.*?>)(.*?<\/label>)/';
      if (preg_match($regex, $html, $label)) {
        $label = str_replace('$', '\$', $label);
        $html = preg_replace($regex, '', $html);
        $html = preg_replace('/(<input type="checkbox".*?>)/', "{$label[1]}$1 {$label[2]}", $html);
      }
      if (isset($options['before'])) {
        $html = str_replace('%before%', $options['before'], $html);
      }
      $html = str_replace('input checkbox', 'form-group', $html);
      $html = str_replace('type="checkbox"', 'type="checkbox" data-toggle="checkbox"', $html);
    }

    if (stristr($html, '<select')) {
      $html = preg_replace('/<select name="(.*?)" class=".*?"/', '<select name="${1}" class="select-block"', $html);
      $html = str_replace('</label>', '</label><br />', $html);
    }

    if (!empty($options['required']) and $options['required'] and preg_match('/^<div class=".*form-group.*"?>/', $html)) {
      $html = str_replace('form-group', 'form-group required', $html);
    }

    return $html;
  }


  public function checkboxInput($fieldName, $options = array())
  {
    $options['type'] = 'checkbox';
    return $this->input($fieldName, $options);
  }


  public function tagInput($fieldName, $options = array())
  {
    // add the class "tag-input" to the input field
    $options['class'][] = 'tag-input';

    // make sure the values passed to the input field, if an array, is restructured into csv
    if (isset($options['value']) and is_array($options['value'])) {
      $options['value'] = implode(',', $options['value']);
    }

    // make sure the values passed to the input field, if an array, is restructured into csv
    if (!empty($this->request->data)) {
      $array_recursion = function (&$array, $depths) use ( &$array_recursion ) {
        $key = $depths[0];

        if (isset($array[$key])) {
          $value = $array[$key];

          if (count($depths)>1) {
            array_shift($depths);
            $array_recursion($array[$key], $depths);
          }
          elseif (is_array($value)) {
            $array[$key] = implode(',', $value);
          }
        }
      };

      $field_name_key = $fieldName;

      if (stristr($field_name_key, '.')) {
        $field_name_key = explode('.', $field_name_key);
        $key = $field_name_key[0];
        array_shift($field_name_key);
        $array_recursion($this->request->data[$key], $field_name_key);
      }
      else {
        $models = array_keys($this->request->data);

        foreach ($models as $model) {
          $array_recursion($this->request->data[$model], array($field_name_key));
        }
      }
    }

    return $this->input($fieldName, $options);
  }


  public function textareaInput($fieldName, $options = array())
  {
    $options['type'] = 'textarea';
    return $this->input($fieldName, $options);
  }


  /**
   * Overwrite FormHelper::_divOptions()
   * Generate inner and outer div options
   * Generate div options for input
   *
   * @param array $options
   * @return array
   */

  protected function _divOptions($options) {
    $this->_inputType = $options['type'];

    $divOptions = array(
      'type' => $options['type'],
      'div' => $this->_inputOptions['wrapInput']
    );
    $this->_divOptions = parent::_divOptions($divOptions);

    $default = array('div' => array('class' => null));
    $options = Hash::merge($default, $options);
    $divOptions = parent::_divOptions($options);
    if ($this->tagIsInvalid() !== false) {
      $divOptions = $this->addClass($divOptions, $this->_inputOptions['errorClass']);
    }
    return $divOptions;
  }


  /**
   * Overwrite FormHelper::_getInput()
   * Wrap `<div>` input element
   * Generates an input element
   *
   * @param type $args
   * @return type
   */

  protected function _getInput($args)
  {
    $input = parent::_getInput($args);
    if ($this->_inputType === 'checkbox' && $this->_inputOptions['checkboxDiv'] !== false) {
      $input = $this->Html->div($this->_inputOptions['checkboxDiv'], $input);
    }

    $beforeInput = $this->_inputOptions['beforeInput'];
    $afterInput = $this->_inputOptions['afterInput'];

    $error = null;
    $errorOptions = $this->_extractOption('error', $this->_inputOptions, null);
    $errorMessage = $this->_extractOption('errorMessage', $this->_inputOptions, true);
    if ($this->_inputType !== 'hidden' && $errorOptions !== false) {
      $errMsg = $this->error($this->_fieldName, $errorOptions);
      if ($errMsg && $errorMessage) {
        $error = $errMsg;
      }
    }

    $html = $beforeInput . $input . $afterInput . $error;

    if ($this->_divOptions) {
      $tag = $this->_divOptions['tag'];
      unset($this->_divOptions['tag']);
      $html = $this->Html->tag($tag, $html, $this->_divOptions);
    }

    return $html;
  }


  /**
   * Overwrite FormHelper::_selectOptions()
   * If $attributes['style'] is `<input type="checkbox">` then replace `<label>` position
   * Returns an array of formatted OPTION/OPTGROUP elements
   *
   * @param array $elements
   * @param array $parents
   * @param boolean $showParents
   * @param array $attributes
   * @return array
   */

  protected function _selectOptions($elements = array(), $parents = array(), $showParents = null, $attributes = array())
  {
    $selectOptions = parent::_selectOptions($elements, $parents, $showParents, $attributes);

    if ($attributes['style'] === 'checkbox') {
      foreach ($selectOptions as $key => $option) {
        $option = preg_replace('/<div.*?>/', '', $option);
        $option = preg_replace('/<\/div>/', '', $option);
        if (preg_match('/>(<label.*?>)/', $option, $match)) {
          $class = $attributes['class'];
          if (preg_match('/.* class="(.*)".*/', $match[1], $classMatch)) {
            $class = $classMatch[1] . ' ' . $attributes['class'];
            $match[1] = str_replace(' class="' . $classMatch[1] . '"', '', $match[1]);
          }
          $option = $match[1] . preg_replace('/<label.*?>/', ' ', $option);
          $option = preg_replace('/(<label.*?)(>)/', '$1 class="' . $class . '"$2', $option);
        }
        $selectOptions[$key] = $option;
      }
    }

    return $selectOptions;
  }


  /**
   * Creates an HTML link, but access the url using the method you specify (defaults to POST).
   * Requires javascript to be enabled in browser.
   *
   * This method creates a `<form>` element. So do not use this method inside an existing form.
   * Instead you should add a submit button using FormHelper::submit()
   *
   * ### Options:
   *
   * - `data` - Array with key/value to pass in input hidden
   * - `method` - Request method to use. Set to 'delete' to simulate HTTP/1.1 DELETE request. Defaults to 'post'.
   * - `confirm` - Can be used instead of $confirmMessage.
   * - Other options is the same of HtmlHelper::link() method.
   * - The option `onclick` will be replaced.
   * - `block` - For nested form. use View::fetch() output form.
   *
   * @param string $title The content to be wrapped by <a> tags.
   * @param string|array $url Cake-relative URL or array of URL parameters, or external URL (starts with http://)
   * @param array $options Array of HTML attributes.
   * @param bool|string $confirmMessage JavaScript confirmation message.
   * @return string An `<a />` element.
   * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::postLink
   */

  public function postLink($title, $url = null, $options = array(), $confirmMessage = false)
  {
    $block = false;
    if (!empty($options['block'])) {
      $block = $options['block'];
      unset($options['block']);
    }

    $fields = $this->fields;
    $this->fields = array();

    $out = parent::postLink($title, $url, $options, $confirmMessage);

    $this->fields = $fields;

    if ($block) {
      $regex = '/<form.*?>.*?<\/form>/';
      if (preg_match($regex, $out, $match)) {
        $this->_View->append($block, $match[0]);
        $out = preg_replace($regex, '', $out);
      }
    }

    return $out;
  }


  public function parentCreate($model = null, $options = array())
  {
    return parent::create($model, $options);
  }


  public function create($model = null, $options = array())
  {
    $default = array('inputDefaults' => array('div'       => 'form-group',
                                              'wrapInput' => false,
                                              'class'     => 'form-control'),
                     'class' => 'well');
    $options = array_merge($default, $options);

    $html = parent::create($model, $options);
    $html .= '<fieldset>';
    return $html;
  }


  public function parentEnd($options = null)
  {
    return parent::end($options);
  }


  public function end($options = null)
  {
    $label = null;
    if (is_string($options)) {
      $label = $options;
      $options = null;
    }
    elseif (isset($options['label'])) {
      $label = $options['label'];
      unset($options['label']);
    }

    $html = '<div class="form-group" style="padding-top: 1em;">';
    $html .= parent::submit($label, array('div' => false, 'class' => 'btn btn-primary'));
    if (!isset($options['cancel']) or is_array($options['cancel'])) {
      $cancel_label   = (!empty($options['cancel']['label']))   ? $options['cancel']['label']   : __('Cancel');
      $cancel_id      = (!empty($options['cancel']['id']))      ? $options['cancel']['id']      : 'cancel-button';
      $cancel_class   = (!empty($options['cancel']['class']))   ? $options['cancel']['class']   : 'btn btn-default';
      $cancel_onclick = (!empty($options['cancel']['onclick'])) ? $options['cancel']['onclick'] : 'history.go(-1); return false;';

      $html .= '&nbsp;';
      $html .= parent::button($cancel_label, array('id' => $cancel_id, 'class' => $cancel_class, 'onclick' => $cancel_onclick));
    }
    unset($options['cancel']);
    $html .= '</div>';
    $html .= '</fieldset>';
    $html .= (empty($options)) ? parent::end() : parent::end($options);

    return $html;
  }


  /**
   * Parent wrapper of FormHelper::file();
   *
   * @param string $fieldName
   * @param array $options
   * @return string
   */
  public function parentFile($fieldName, $options = array())
  {
    return parent::file($fieldName, $options);
  }


  /**
   * Overwrite of FormHelper::file()
   * Generates a form file input widget complete with label and wrapper div
   *
   * ### Options
   *
   * - `label` - Either a string label, or an array of options for the label. See FormHelper::label().
   * - `unlock_hidden` - Boolean flag to unlock the hidden field that accompanies the file field.
   * - `value` - Current value to set for the field.
   * - `after` - Content to place after the label + field.
   *
   * @param string $fieldName Name of a field, in the form "Modelname.fieldname"
   * @param array $options Array of HTML attributes.
   */

  public function file($fieldName, $options)
  {
    $this->_fieldName = $fieldName;

    $options = Hash::merge($this->_inputDefaults, $options);

    $this->_inputOptions = $options;

    $label = isset($options['label']) ? $options['label'] : str_replace('_', ' ', $fieldName);
    $label = ($label !== false) ? $this->label($label) : '';

    $fileinput_new_or_exists = empty($options['value']) ? 'fileinput-new' : 'fileinput-exists';

    if (isset($options['unlock_hidden']) and $options['unlock_hidden'] == true) {
      $this->unlockField($fieldName .'_hidden');
      unset($options['unlock_hidden']);
    }

    $hidden_field = $this->hidden($fieldName .'_hidden', array('value' => (empty($options['value'])) ? '' : $options['value']));

    $parent_html = parent::file($fieldName, $options);

    $fileinput_filename = empty($options['value']) ? '' : $options['value'];

    $after = '';
    if (!empty($options['after'])) {
      $after = $options['after'];
      unset($options['after']);
    }

    $html = <<<HTML
<div class="form-group">
  {$label}
  <div class="fileinput {$fileinput_new_or_exists}" data-provides="fileinput">
    {$hidden_field}
    <span class="btn btn-primary btn-embossed btn-file">
      <span class="fileinput-new"><span class="fui-upload"></span>&nbsp;&nbsp;Attach File</span>
      <span class="fileinput-exists"><span class="fui-gear"></span>&nbsp;&nbsp;Change</span>
      {$parent_html}
    </span>
    <span class="fileinput-filename">{$fileinput_filename}</span>
    <a href="#" class="close fileinput-exists" data-dismiss="fileinput" style="float: none">×</a>
    {$after}
  </div>
</div>
HTML;

    return $html;
  }


  /**
   * Create a slider (and hidden input) form field
   *
   * @param string $fieldName
   * @param array $options
   */

  public function slider($fieldName, $options = array())
  {
    $fieldId = Inflector::camelize($fieldName);

    $min = (!empty($options['min'])) ? $options['min'] : 0;
    $max = (!empty($options['max'])) ? $options['max'] : 10;
    $value = 0;
    if (strpos($fieldName, '.') > 0) {
      $model = '';
      $label = '';
      list($model, $label) = explode('.', $fieldName, 2);
      $value = (isset($this->data[$model][$label])) ? $this->data[$model][$label] : $value;
      $sliderId = 'slider-' . str_replace(array('.', '_'), '-', Inflector::underscore($fieldName));
    }
    else {
      $value = (isset($this->data[$this->defaultModel][$fieldName])) ? $this->data[$this->defaultModel][$fieldName] : $value;
      $fieldId = $this->defaultModel . Inflector::camelize($fieldName);
      $sliderId = 'slider-' . str_replace('_', '-', Inflector::underscore($this->defaultModel . '_' . $fieldName));
    }
    $value = (!empty($options['value'])) ? $options['value'] : $value;
    $max = ($value > $max) ? $value : $max;
    $min = ($value < $min) ? $value : $min;

    $orientation = (!empty($options['orientation'])) ? $options['orientation'] : 'horizontal';
    $label = (!empty($options['label'])) ? $options['label'] : Inflector::humanize($fieldName);

    foreach (array('min', 'max', 'orientation', 'label') as $unset) {
      unset($options[$unset]);
    }

    $html = $this->hidden($fieldName, $options);

    $html .= <<<HTML
<div class="form-group">
  <label for="{$sliderId}">{$label}</label>
  <div id="{$sliderId}">
    <span class="ui-slider-value first" id="{$sliderId}-first">{$value}</span>
  </div>
</div>
<script type="text/javascript">
if ($("#{$sliderId}").length > 0) {
  $("#{$sliderId}").slider({
    min: {$min},
    max: {$max},
    value: {$value},
    orientation: "{$orientation}",
    range: "min",
    slide: function(event, ui) {
      $("#{$sliderId}-first").html(ui.value);
      $("#{$fieldId}").val(ui.value);
    }
  });
}
</script>
HTML;
    return $html;
  }


  /**
   * Create a slider (and hidden input) form field
   *
   * @param string $fieldName
   * @param array $options
   */

  public function sliderSelect($fieldName, $options = array())
  {
    $fieldId = Inflector::camelize($fieldName);
    $json_options = json_encode($options['options']);

    $min = 0;
    $max = count($options['options']) - 1;

    $value = 0;
    if (strpos($fieldName, '.') > 0) {
      $model = '';
      $label = '';
      list($model, $label) = explode('.', $fieldName, 2);
      $value = (isset($this->data[$model][$label])) ? $this->data[$model][$label] : $value;
      $sliderId = 'slider-' . str_replace(array('.', '_'), '-', Inflector::underscore($fieldName));
    }
    else {
      $value = (isset($this->data[$this->defaultModel][$fieldName])) ? $this->data[$this->defaultModel][$fieldName] : $value;
      $fieldId = $this->defaultModel . Inflector::camelize($fieldName);
      $sliderId = 'slider-' . str_replace('_', '-', Inflector::underscore($this->defaultModel . '_' . $fieldName));
    }
    $value = (!empty($options['value'])) ? $options['value'] : $value;

    $value = ($value > $max) ? $max : $value;
    $value = ($value < $min) ? $min : $value;

    $json_var = 'options' . Inflector::camelize(str_replace('-', '_', $sliderId));
    $orientation = (!empty($options['orientation'])) ? $options['orientation'] : 'horizontal';
    $label = (!empty($options['label'])) ? $options['label'] : Inflector::humanize($fieldName);

    $slider_options = $options['options'];
    foreach (array('min', 'max', 'orientation', 'label', 'options') as $unset) {
      unset($options[$unset]);
    }

    $html = $this->hidden($fieldName, $options);

    $html .= <<<HTML
      <div class="form-group">
        <label for="{$sliderId}">{$label}</label>
        <div id="{$sliderId}">
          <span class="ui-slider-value first" id="{$sliderId}-first">{$slider_options[$value]}</span>
        </div>
      </div>
      <script type="text/javascript">
      var {$json_var} = {$json_options};
      if ($("#{$sliderId}").length > 0) {
        $("#{$sliderId}").slider({
          min: {$min},
          max: {$max},
          value: {$value},
          orientation: "{$orientation}",
          range: "min",
          slide: function(event, ui) {
            $("#{$sliderId}-first").html({$json_var}[ui.value]);
            $("#{$fieldId}").val(ui.value);
          }
        });
      }
      </script>
HTML;
    return $html;
  }

}
