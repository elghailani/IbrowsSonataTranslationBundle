<?php

namespace Ibrows\SonataTranslationBundle\Admin;

use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitManagerInterface;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

abstract class TranslationAdmin extends AbstractAdmin
{
    /**
     * @var TransUnitManagerInterface
     */
    protected $transUnitManager;
    /**
     * @var array
     */
    protected $editableOptions;

    /**
     * @var array
     */
    protected $defaultSelections = array();

    /**
     * @var array
     */
    protected $emptyFieldPrefixes = array();

    /**
     * @var array
     */
    protected $filterLocales = array();

    /**
     * @var array
     */
    protected $managedLocales = array();

    /**
     * @param array $options
     */
    public function setEditableOptions(array $options)
    {
        $this->editableOptions = $options;
    }

    /**
     * @param TransUnitManagerInterface $translationManager
     */
    public function setTransUnitManager(TransUnitManagerInterface $translationManager)
    {
        $this->transUnitManager = $translationManager;
    }

    /**
     * @param array $managedLocales
     */
    public function setManagedLocales(array $managedLocales)
    {
        $this->managedLocales = $managedLocales;
    }

    /**
     * @return array
     */
    public function getEmptyFieldPrefixes()
    {
        return $this->emptyFieldPrefixes;
    }

    /**
     * @return array
     */
    public function getDefaultSelections()
    {
        return $this->defaultSelections;
    }

    /**
     * @param array $selections
     */
    public function setDefaultSelections(array $selections)
    {
        $this->defaultSelections = $selections;
    }

    /**
     * @param array $prefixes
     */
    public function setEmptyPrefixes(array $prefixes)
    {
        $this->emptyFieldPrefixes = $prefixes;
    }

    /**
     * @return array
     */
    // public function getFilterParameters()
    // {
    //     $this->datagridValues = array_merge(
    //         array(
    //             'domain' => array(
    //                 'value' => $this->getDefaultDomain(),
    //             ),
    //         ),
    //         $this->datagridValues
    //     );

    //     return parent::getFilterParameters();
    // }

    /**
     * @param unknown $name
     *
     * @return multitype:|NULL
     */
    public function getTemplate($name)
    {
        if ($name === 'layout') {
            return 'IbrowsSonataTranslationBundle::translation_layout.html.twig';
        }

        if ($name === 'list') {
            return 'IbrowsSonataTranslationBundle:CRUD:list.html.twig';
        }

        return parent::getTemplate($name);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getOriginalTemplate($name)
    {
        return parent::getTemplate($name);
    }

    /**
     * @param RouteCollection $collection
     */
    protected function configureRoutes(RouteCollectionInterface $collection): void

    {
        $collection
            ->add('clear_cache')
            ->add('create_trans_unit');
    }

    /**
     * @param ListMapper $list
     */
    protected function configureListFields(ListMapper $list): void
    {
        // check if in opbundle for more information.
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // https://stackoverflow.com/questions/21615374/how-do-i-check-for-the-existence-of-a-bundle-in-twig
        $isEntity = !$em->getMetadataFactory()->isTransient('OnePx\BaseBundle\Entity\I18N\LexikHelper');

        $list
            ->add('id', 'integer')
            ->add('key', 'string')
            ->add('domain', 'string');

        if ($isEntity == true) {
            $list->add(
                'opxHelper',
                'string',
                array(
                    'mapped' => false,
                    'sortable' => false,
                    'template' => 'OnePxBaseBundle:sonataAdmin:customListFields/lexik.helper.html.twig',
                )
            );
        }

        $localesToShow = count($this->filterLocales) > 0 ? $this->filterLocales : $this->managedLocales;

        foreach ($localesToShow as $locale) {
            $fieldDescription = $this->modelManager->getNewFieldDescriptionInstance($this->getClass(), $locale);
            $fieldDescription->setTemplate(
                'IbrowsSonataTranslationBundle:CRUD:base_inline_translation_field.html.twig'
            );
            $fieldDescription->setOption('locale', $locale);
            $fieldDescription->setOption('editable', $this->editableOptions);
            $list->add($fieldDescription);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildDatagrid()
    {
        if ($this->datagrid) {
            return;
        }

        $filterParameters = $this->getFilterParameters();

        // transform _sort_by from a string to a FieldDescriptionInterface for the datagrid.
        if (isset($filterParameters['locale']) && is_array($filterParameters['locale'])) {
            $this->filterLocales = array_key_exists('value', $filterParameters['locale']) ? $filterParameters['locale']['value'] : $this->managedLocales;
        }

        parent::buildDatagrid();
    }

    /**
     * @param FormMapper $form
     */
    protected function configureFormFields(FormMapper $form): void
    {
        $subject = $this->getSubject();

        if (null === $subject->getId()) {
            $subject->setDomain($this->getDefaultDomain());
        }

        $form
            ->add('key', TextType::class)
            ->add('domain', TextType::class);
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->getConfigurationPool()->getContainer();
    }

    /**
     * @return string
     */
    protected function getDefaultDomain()
    {
        return $this->getContainer()->getParameter('ibrows_sonata_translation.defaultDomain');
    }

    /**
     * {@inheritdoc}
     */
   protected function configureBatchActions(array $actions): array
    {
        $actions = parent::getBatchActions();
        $actions['download'] = array(
            'label'            => $this->trans($this->getLabelTranslatorStrategy()->getLabel('download', 'batch', 'IbrowsSonataTranslationBundle')),
            'ask_confirmation' => false,
        );

        return $actions;
    }
}
