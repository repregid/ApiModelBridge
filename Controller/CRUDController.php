<?php

namespace Repregid\ApiModelBridge\Controller;


use Bezb\ModelBundle\Component\BaseScenario;
use Repregid\ApiBundle\Controller\CRUDController as ApiCRUDController;
use Bezb\ModelBundle\Component\ModelFactory;
use Bezb\ModelBundle\Component\ModelFactoryInterface;
use Bezb\ModelBundle\Component\ModelInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CRUDController
 * @package Repregid\ApiModelBridge\Controller
 */
class CRUDController extends ApiCRUDController
{
    /**
     * @var ModelFactoryInterface
     */
    protected $modelFactory;

    /**
     * CRUDController constructor.
     *
     * @param FormFactoryInterface $formFactory
     * @param ModelFactory $modelFactory
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        ModelFactory $modelFactory
    ) {
        parent::__construct($formFactory);
        $this->modelFactory = $modelFactory;
    }

    /**
     * @param string $entity
     * @return ModelInterface
     */
    protected function model(string $entity): ModelInterface
    {
        return $this->modelFactory->create($entity);
    }

    /**
     * @param Request $request
     * @param string $entity
     * @param array $groups
     * @param string $formType
     * @param string $formMethod
     * @param string $scenario
     * @return View
     */
    public function createAction(
        Request $request,
        string $entity,
        array $groups,
        string $formType,
        string $formMethod,
        string $scenario = BaseScenario::CREATE
    ) : View
    {
        $model  = $this->model($entity);
        $form   = $this->form($formType, $formMethod);

        $model->setForm($form);
        $model->setScenario($scenario);

        try {
            if(!$model->save()) {
                return $this->renderFormError($form);
            }
        } catch (\Exception $e) {
            return $this->renderInternalError($e->getMessage());
        }

        return $this->renderCreated($model->getEntity(), $groups);
    }

    /**
     * @param Request $request
     * @param string $entity
     * @param array $groups
     * @param string $formType
     * @param string $formMethod
     * @param $id
     * @param string $scenario
     * @return View
     */
    public function updateAction(
        Request $request,
        string $entity,
        array $groups,
        string $formType,
        string $formMethod,
        $id,
        string $scenario = BaseScenario::UPDATE
    ) : View
    {
        $form   = $this->form($formType, $formMethod);
        $model  = $this->model($entity);

        if(!$model->findBy(['id' => $id])) {
            return $this->renderNotFound();
        }

        $model
            ->setForm($form)
            ->setScenario($scenario);

        try {
            if(!$model->save()) {
                return $this->renderFormError($form);
            }
        } catch (\Exception $e) {
            return $this->renderInternalError($e->getMessage());
        }

        return $this->renderOk($model->getEntity(), $groups);
    }

    /**
     * @param Request $request
     * @param string $entity
     * @param $id
     * @return View
     */
    public function deleteAction(Request $request, string $entity, $id) : View
    {
        $model = $this->model($entity);

        if(!$model->findBy(['id' => $id])) {
            return $this->renderNotFound();
        }

        try {
            $model->delete();
        } catch (\Exception $e) {
            return $this->renderInternalError($e->getMessage());
        }

        return $this->renderResponse(['message' => 'item has been deleted']);
    }
}