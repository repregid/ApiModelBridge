<?php

namespace Repregid\ApiModelBridge\Controller;


use Bezb\ModelBundle\Component\BaseScenario;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Repregid\ApiBundle\Controller\CRUDController as ApiCRUDController;
use Bezb\ModelBundle\Component\ModelFactory;
use Bezb\ModelBundle\Component\ModelFactoryInterface;
use Bezb\ModelBundle\Component\ModelInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
     * @param EventDispatcherInterface $dispatcher
     * @param ModelFactory $modelFactory
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        EventDispatcherInterface $dispatcher,
        FilterBuilderUpdaterInterface $filterBuilderUpdater,
        ModelFactory $modelFactory
    ) {
        parent::__construct($formFactory, $dispatcher, $filterBuilderUpdater);
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
     * @param string $context
     * @param string $entity
     * @param array $groups
     * @param array $security
     * @param string $formType
     * @param string $formMethod
     * @param string $scenario
     * @return View
     */
    public function createAction(
        Request $request,
        string $context,
        string $entity,
        array $groups,
        array $security,
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
            if(!$model->validate()) {
                return $this->renderFormError($form);
            }

            $obj = $model->getEntity();
            if (!empty($security)) {
                $this->denyAccessUnlessGrantedAny($security, $obj);
            }
            $model->save(false);
        } catch (\Exception $e) {
            return $this->renderInternalError($e->getMessage());
        }

        return $this->renderCreated($model->getEntity(), $groups);
    }

    /**
     * @param Request $request
     * @param string $context
     * @param string $entity
     * @param array $groups
     * @param array $security
     * @param string $formType
     * @param string $formMethod
     * @param $id
     * @param string $idName
     * @param string $scenario
     * @return View
     */
    public function updateAction(
        Request $request,
        string $context,
        string $entity,
        array $groups,
        array $security,
        string $formType,
        string $formMethod,
        $id,
        string $idName = 'id',
        string $scenario = BaseScenario::UPDATE
    ) : View
    {
        $form   = $this->form($formType, $formMethod);
        $model  = $this->model($entity);

        if(!$model->findBy([$idName => $id])) {
            return $this->renderNotFound();
        }

        if (!empty($security)) {
            $this->denyAccessUnlessGrantedAny($security, $model->getEntity());
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
     * @param string $context
     * @param string $entity
     * @param array $security
     * @param $id
     * @param $idName
     * @return View
     */
    public function deleteAction(
        Request $request,
        string $context,
        string $entity,
        array $security,
        $id,
        string $idName = 'id'
    ) : View
    {
        $model = $this->model($entity);

        if(!$model->findBy([$idName => $id])) {
            return $this->renderNotFound();
        }
        if (!empty($security)) {
            $this->denyAccessUnlessGrantedAny($security, $model->getEntity());
        }

        try {
            $model->delete();
        } catch (\Exception $e) {
            return $this->renderInternalError($e->getMessage());
        }

        return $this->renderResponse(['message' => 'item has been deleted']);
    }
}