<?php

class PageController extends Controller {

    public function actionIndex() {
        $dataProvider = new CActiveDataProvider('Page');
        $dataProvider = new CActiveDataProvider('Page', array(
                    'criteria' => array(
                        'condition' => "status='published' OR user_id='" . Yii::app()->user->id . "'",
//                        'with' => array('author'),
                    ),
                    'pagination' => array(
                        'pageSize' => 20,
                    ),
                ));
        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

    public function actionView($id) {
        $this->render('view', array(
            'model' => $this->loadModel($id),
        ));
    }

    public function actionCreate() {
        $model = new Page;
        if (isset($_POST['Page'])) {
            $model->setAttributes($_POST['Page']);

            if (isset($_POST['Page']['user']))
                $model->user = $_POST['Page']['user'];
            else
                $model->user = Yii::app()->user->id;

            if (isset($_POST['Page']['parent']))
                $model->parent = $_POST['Page']['parent'];

            if (isset($_POST['Page']['categories']))
                $model->categories = $_POST['Page']['categories'];

            try {
                if ($model->save()) {
                    if (isset($_GET['returnUrl'])) {
                        $this->redirect($_GET['returnUrl']);
                    } else {
                        $this->redirect(array('view', 'id' => $model->id));
                    }
                }
            } catch (Exception $e) {
                $model->addError('', $e->getMessage());
            }
        } elseif (isset($_GET['Page'])) {
            $model->attributes = $_GET['Page'];
        }
        $baseUrl = Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('application.modules.page.assets'));
        Yii::app()->getClientScript()->registerScriptFile($baseUrl . '/form.js');
        $model->user = Yii::app()->user->id;
        $this->render('create', array('model' => $model));
    }

    public function actionUpdate($id) {
        $model = $this->loadModel($id);

        if (isset($_POST['Page'])) {
            $model->setAttributes($_POST['Page']);
            if (isset($_POST['Page']['parent']))
                $model->parent = $_POST['Page']['parent'];
            else
                $model->parent = array();

            if (isset($_POST['Page']['user']))
                $model->user = $_POST['Page']['user'];

            if (isset($_POST['Page']['categories']))
                $model->categories = $_POST['Page']['categories'];
            else
                $model->categories = array();
            try {
                if ($model->save()) {
                    if (isset($_GET['returnUrl'])) {
                        $this->redirect($_GET['returnUrl']);
                    } else {
                        $this->redirect(array('view', 'id' => $model->id));
                    }
                }
            } catch (Exception $e) {
                $model->addError('', $e->getMessage());
            }
        }
        $baseUrl = Yii::app()->getAssetManager()->publish('application.modules.user.views.asset');
        Yii::app()->getClientScript()->registerScriptFile($baseUrl . '/form.js');
        $this->render('update', array(
            'model' => $model,
        ));
    }

    public function actionDelete($id) {
        if (Yii::app()->request->isPostRequest) {
            try {
                $this->loadModel($id)->delete();
            } catch (Exception $e) {
                throw new CHttpException(500, $e->getMessage());
            }

            if (!Yii::app()->getRequest()->getIsAjaxRequest()) {
                $this->redirect(array('admin'));
            }
        }
        else
            throw new CHttpException(400,
                    Yii::t('app', 'Invalid request.'));
    }

    public function actionAdmin() {
        $model = new Page('search');
        $model->unsetAttributes();

        if (isset($_GET['Page']))
            $model->setAttributes($_GET['Page']);

        $this->render('admin', array(
            'model' => $model,
        ));
    }

    public function actionToggle($id, $attribute, $model) {
        if (Yii::app()->request->isPostRequest) {
            // we only allow deletion via POST request
            $model = $this->loadModel($id, $model);
            //loadModel($id, $model) from giix
            ($model->$attribute == 1) ? $model->$attribute = 0 : $model->$attribute = 1;
            $model->save();

            // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
            if (!isset($_GET['ajax']))
                $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
        }
        else
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
    }

    public function loadModel($id) {
        $model = Page::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, Yii::t('app', 'The requested page does not exist.'));
        return $model;
    }

}