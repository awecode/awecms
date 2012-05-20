<?php

Yii::import('system.gii.generators.crud.CrudCode');

class AweCrudCode extends CrudCode {

    public $authtype = 'no_access_control';
    public $validation = 2;
    public $baseControllerClass = 'Controller';
    public $identificationColumn = '';
    public $isJToggleColumnEnabled = true;
    
    public $dateTypes = array('datetime', 'date', 'time');
    public $booleanTypes = array('tinyint(1)', 'boolean', 'bool');
    public $emailFields = array('email', 'e-mail', 'email_address', 'e-mail_address', 'emailaddress', 'e-mailaddress');
    public $imageFields = array('image', 'picture', 'photo', 'pic', 'profile_pic', 'profile_picture', 'avatar', 'profilepic', 'profilepicture');
    public $urlFields = array('url', 'link', 'uri', 'homepage', 'webpage', 'website', 'profile_url', 'profile_link');
    public $create_time = array('create_time', 'createtime', 'created_at', 'createdat', 'created_time', 'createdtime');
    public $update_time = array('changed', 'changed_at', 'updatetime', 'modified_at', 'updated_at', 'update_time', 'timestamp', 'updatedat');

    public function rules() {
        return array_merge(parent::rules(), array(
                    array('identificationColumn, isJToggleColumnEnabled, validation, authtype', 'safe'),
                ));
    }

    public function attributeLabels() {
        return array_merge(parent::attributeLabels(), array(
                    'authtype' => 'Authentication type',
                ));
    }

    //used by getIdentificationColumn as callback for array_map
    private static function getName($column) {
        return $column->name;
    }

    public function getIdentificationColumn() {
        if (!empty($this->identificationColumn))
            return $this->identificationColumn;

        $possibleIdentifiers = array('name', 'title', 'slug');


        $columns_name = array_map('self::getName', $this->tableSchema->columns);
        foreach ($possibleIdentifiers as $possibleIdentifier) {
            if (in_array($possibleIdentifier, $columns_name))
                return $possibleIdentifier;
        }

        foreach ($columns_name as $column_name) {
            if (preg_match('/.*name.*/', $column_name, $matches)) {
                return $column_name;
            }
        }

        foreach ($this->tableSchema->columns as $column) {
            if (!$column->isForeignKey
                    && !$column->isPrimaryKey
                    && $column->type != 'INT'
                    && $column->type != 'INTEGER'
                    && $column->type != 'BOOLEAN') {
                return $column->name;
            }
        }

        if (is_array($pk = $this->tableSchema->primaryKey))
            $pk = $pk[0];
        //every table must have a PK
        return $pk;
    }

    public function getDetailViewAttribute($column) {
        if ($column->name == 'id') {
            return "array(
                        'name'=>'id', // only admin user can see person id
                        'label'=>'ID',
                        'visible'=>Yii::app()->getModule('user')->isAdmin()
                    ),";
        }

        if (in_array(strtolower($column->name), $this->imageFields)) {
            return "array(
                        'name'=>'{$column->name}',
                        'type'=>'image'
                    ),";
        }

        if (in_array(strtolower($column->name), $this->emailFields)) {
            return "array(
                        'name'=>'{$column->name}',
                        'type'=>'email'
                    ),";
        }

        if (in_array(strtolower($column->name), $this->urlFields)) {
            return "array(
                        'name'=>'{$column->name}',
                        'type'=>'url'
                    ),";
        }

        $type_conversion = array(
            'longtext' => 'ntext',
            'time' => 'time',
            'boolean' => 'boolean',
            'bool' => 'boolean',
            'tinyint(1)' => 'boolean',
        );

        if (array_key_exists(strtolower($column->dbType), $type_conversion)) {
            return "array(
                        'name'=>'{$column->name}',
                        'type'=>'" . $type_conversion[strtolower($column->dbType)] . "'
                    ),";
        }

        return "'{$column->name}',";
    }

    public function generateField($column, $modelClass) {
        if (in_array(strtolower($column->dbType), $this->booleanTypes))
            return "echo \$form->checkBox(\$model,'{$column->name}')";
        //if the column name looks like that of an image and if it's a string
        if (in_array(strtolower($column->name), $this->imageFields) && $column->type == 'string') {
            //find maximum length and size
            if (($size = $maxLength = $column->size) > 60)
                $size = 60;
            //generate the textField
            $string = "echo \$form->textField(\$model,'{$column->name}',array('size'=>$size,'maxlength'=>$maxLength))";
            //also show the image and make it clickable if the field the something
            $string .= ";\nif (!empty(\$model->{$column->name})){ ?> <div class=\"right\"><a href=\"<?php echo \$model->{$column->name} ?>\" target=\"_blank\" title=\"<?php echo Awecms::generateFriendlyName('{$column->name}') ?>\"><img src=\"<?php echo \$model->{$column->name} ?>\"  alt=\"<?php echo Awecms::generateFriendlyName('{$column->name}') ?>\" title=\"<?php echo Awecms::generateFriendlyName('{$column->name}') ?>\"/></a></div><?php }";
            return $string;
        } else if (strtolower($column->dbType) == 'longtext') {
            //TODO integrate markitup
            return "echo \$form->textArea(\$model,'{$column->name}',array('rows'=>6, 'cols'=>50))";
        } else if (stripos($column->dbType, 'text') !== false)
            return "echo \$form->textArea(\$model,'{$column->name}',array('rows'=>6, 'cols'=>50))";
        else if (substr(strtoupper($column->dbType), 0, 4) == 'ENUM') {
            $string = sprintf("echo CHtml::activeDropDownList(\$model, '%s', array(\n", $column->name);

            $enum_values = explode(',', substr($column->dbType, 4, strlen($column->dbType) - 1));

            foreach ($enum_values as $value) {
                $value = trim($value, "()'");
                $string .= "\t\t\t'$value' => Yii::t('app', '" . Awecms::generateFriendlyName($value) . "') ,\n";
            }
            $string .= '))';

            return $string;
        } else if (in_array(strtolower($column->dbType), $this->dateTypes)) {
            return ("\$this->widget('CJuiDateTimePicker',
						 array(
							'model'=>\$model,
                                                        'name'=>'{$modelClass}[{$column->name}]',
							'language'=> substr(Yii::app()->language,0,strpos(Yii::app()->language,'_')),
							'value'=>\$model->{$column->name},
                                                        'mode' => '" . strtolower($column->dbType) . "',
							'options'=>array(
                                                                        'showAnim'=>'fold', // 'show' (the default), 'slideDown', 'fadeIn', 'fold'
                                                                        'showButtonPanel'=>true,
                                                                        'changeYear'=>true,
                                                                        'changeMonth'=>true,
                                                                        'dateFormat'=>'yy-mm-dd',
                                                                        ),
                                                    )
					);
					");
        } else {
            if (preg_match('/^(password|pass|passwd|passcode)$/i', $column->name))
                $inputField = 'passwordField';
            else
                $inputField = 'textField';

            if ($column->type !== 'string' || $column->size === null)
                return "echo \$form->{$inputField}(\$model,'{$column->name}')";
            else {
                if (($size = $maxLength = $column->size) > 60)
                    $size = 60;
                return "echo \$form->{$inputField}(\$model,'{$column->name}',array('size'=>$size,'maxlength'=>$maxLength))";
            }
        }
    }

    public function generateGridViewColumn($column) {

        // Boolean or bit.
        if (strtoupper($column->dbType) == 'TINYINT(1)'
                || strtoupper($column->dbType) == 'BIT'
                || strtoupper($column->dbType) == 'BOOL'
                || strtoupper($column->dbType) == 'BOOLEAN') {
            if ($this->isJToggleColumnEnabled) {
                return "array(
                                        'class' => 'JToggleColumn',
					'name' => '{$column->name}',
					'filter' => array('0' => Yii::t('app', 'No'), '1' => Yii::t('app', 'Yes')),
                                        'model' => get_class(\$model),
                                        'htmlOptions' => array('style' => 'text-align:center;min-width:60px;')
					)";
            }else
                return "array(
					'name' => '{$column->name}',
					'value' => '(\$data->{$column->name} === 0) ? Yii::t(\\'app\\', \\'No\\') : Yii::t(\\'app\\', \\'Yes\\')',
					'filter' => array('0' => Yii::t('app', 'No'), '1' => Yii::t('app', 'Yes')),
					)";
        } else // Common column.
            return "'{$column->name}'";

        //TODO relation mappings here
    }

}