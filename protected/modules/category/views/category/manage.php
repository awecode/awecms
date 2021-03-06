<?php
$this->breadcrumbs = array(
    Yii::t('app', 'Categories') => array('/category'),
    Yii::t('app', 'Manage'),
);
if (!isset($this->menu) || $this->menu === array())
    $this->menu = array(
        array('label' => Yii::t('app', 'All Categories'), 'url' => array('/category')),
        array('label' => Yii::t('app', 'Create New Category'), 'url' => array('/category/category/create')),
        array('label' => Yii::t('app', 'Manage All Categories')),
    );
?>

<h1> <?php echo Yii::t('app', 'Manage'); ?> <?php echo Yii::t('app', 'Categories'); ?> </h1>

<?php
if (count($model->search()->data)) {
    $this->widget('zii.widgets.grid.CGridView', array(
        'id' => 'category-grid',
        'dataProvider' => $model->search(),
        'filter' => $model,
        'columns' => array(
            'name',
            'description',
            array(
                'class' => 'CButtonColumn',
            ),
        ),
    ));
} else {
    echo Yii::t('app', 'No results found!');
}