<?php

namespace SS6\ShopBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use SS6\ShopBundle\Form\Admin\Product\QuickSearchFormType;
use SS6\ShopBundle\Model\AdminNavigation\MenuItem;
use SS6\ShopBundle\Model\Category\CategoryFacade;
use SS6\ShopBundle\Model\Grid\QueryBuilderDataSource;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends Controller {

	/**
	 * @var \SS6\ShopBundle\Model\Category\CategoryFacade
	 */
	private $categoryFacade;

	public function __construct(CategoryFacade $categoryFacade) {
		$this->categoryFacade = $categoryFacade;
	}

	/**
	 * @Route("/product/edit/{id}", requirements={"id" = "\d+"})
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param int $id
	 */
	public function editAction(Request $request, $id) {
		$flashMessageSender = $this->get('ss6.shop.flash_message.sender.admin');
		/* @var $flashMessageSender \SS6\ShopBundle\Model\FlashMessage\FlashMessageSender */
		$productEditFacade = $this->get('ss6.shop.product.product_edit_facade');
		/* @var $productEditFacade \SS6\ShopBundle\Model\Product\ProductEditFacade */
		$productDetailFactory = $this->get('ss6.shop.product.product_detail_factory');
		/* @var $productDetailFactory \SS6\ShopBundle\Model\Product\Detail\ProductDetailFactory */
		$productEditFormTypeFactory = $this->get('ss6.shop.form.admin.product.product_edit_form_type_factory');
		/* @var $productEditFormTypeFactory \SS6\ShopBundle\Form\Admin\Product\ProductEditFormTypeFactory */
		$productEditDataFactory = $this->get('ss6.shop.product.product_edit_data_factory');
		/* @var $productEditDataFactory \SS6\ShopBundle\Model\Product\ProductEditDataFactory */

		$product = $productEditFacade->getById($id);

		$form = $this->createForm($productEditFormTypeFactory->create($product));
		$productEditData = $productEditDataFactory->createFromProduct($product);

		$form->setData($productEditData);
		$form->handleRequest($request);

		if ($form->isValid()) {
			$productEditFacade->edit($id, $form->getData());

			$flashMessageSender->addSuccessFlashTwig('Bylo upraveno zboží <strong>{{ name }}</strong>', [
				'name' => $product->getName(),
			]);
			return $this->redirect($this->generateUrl('admin_product_edit', ['id' => $product->getId()]));
		}

		if ($form->isSubmitted() && !$form->isValid()) {
			$flashMessageSender->addErrorFlashTwig('Prosím zkontrolujte si správnost vyplnění všech údajů');
		}

		$breadcrumb = $this->get('ss6.shop.admin_navigation.breadcrumb');
		/* @var $breadcrumb \SS6\ShopBundle\Model\AdminNavigation\Breadcrumb */
		$breadcrumb->replaceLastItem(new MenuItem('Editace zboží - ' . $product->getName()));

		return $this->render('@SS6Shop/Admin/Content/Product/edit.html.twig', [
			'form' => $form->createView(),
			'product' => $product,
			'productDetail' => $productDetailFactory->getDetailForProduct($product),
			'productSellingPricesIndexedByDomainId' => $productEditFacade->getAllProductSellingPricesIndexedByDomainId($product),
			'productMainCategoriesIndexedByDomainId' => $this->categoryFacade->getProductMainCategoriesIndexedByDomainId($product),
		]);
	}

	/**
	 * @Route("/product/new/")
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 */
	public function newAction(Request $request) {
		$flashMessageSender = $this->get('ss6.shop.flash_message.sender.admin');
		/* @var $flashMessageSender \SS6\ShopBundle\Model\FlashMessage\FlashMessageSender */
		$productEditFormTypeFactory = $this->get('ss6.shop.form.admin.product.product_edit_form_type_factory');
		/* @var $productEditFormTypeFactory \SS6\ShopBundle\Form\Admin\Product\ProductEditFormTypeFactory */
		$productEditDataFactory = $this->get('ss6.shop.product.product_edit_data_factory');
		/* @var $productEditDataFactory \SS6\ShopBundle\Model\Product\ProductEditDataFactory */
		$pricingGroupFacade = $this->get('ss6.shop.pricing.group.pricing_group_facade');
		/* @var $pricingGroupFacade \SS6\ShopBundle\Model\Pricing\Group\PricingGroupFacade */

		$form = $this->createForm($productEditFormTypeFactory->create());

		$productEditData = $productEditDataFactory->createDefault();

		$form->setData($productEditData);
		$form->handleRequest($request);

		if ($form->isValid()) {
			$productEditFacade = $this->get('ss6.shop.product.product_edit_facade');
			/* @var $productEditFacade \SS6\ShopBundle\Model\Product\ProductEditFacade */
			$product = $productEditFacade->create($form->getData());

			$flashMessageSender->addSuccessFlashTwig('Bylo vytvořeno zboží'
					. ' <strong><a href="{{ url }}">{{ name }}</a></strong>', [
				'name' => $product->getName(),
				'url' => $this->generateUrl('admin_product_edit', ['id' => $product->getId()]),
			]);
			return $this->redirect($this->generateUrl('admin_product_list'));
		}

		if ($form->isSubmitted() && !$form->isValid()) {
			$flashMessageSender->addErrorFlashTwig('Prosím zkontrolujte si správnost vyplnění všech údajů');
		}

		return $this->render('@SS6Shop/Admin/Content/Product/new.html.twig', [
			'form' => $form->createView(),
			'pricingGroupsIndexedByDomainId' => $pricingGroupFacade->getAllIndexedByDomainId(),
		]);
	}

	/**
	 * @Route("/product/list/")
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 */
	public function listAction(Request $request) {
		$administratorGridFacade = $this->get('ss6.shop.administrator.administrator_grid_facade');
		/* @var $administratorGridFacade \SS6\ShopBundle\Model\Administrator\AdministratorGridFacade */
		$administrator = $this->getUser();
		/* @var $administrator \SS6\ShopBundle\Model\Administrator\Administrator */
		$gridFactory = $this->get('ss6.shop.grid.factory');
		/* @var $gridFactory \SS6\ShopBundle\Model\Grid\GridFactory */
		$productListAdminFacade = $this->get('ss6.shop.product.list.product_list_admin_facade');
		/* @var $productListAdminFacade \SS6\ShopBundle\Model\Product\Listing\ProductListAdminFacade */
		$advancedSearchFacade = $this->get('ss6.shop.advanced_search.advanced_search_facade');
		/* @var $advancedSearchFacade \SS6\ShopBundle\Model\AdvancedSearch\AdvancedSearchFacade */

		$advancedSearchForm = $advancedSearchFacade->createAdvancedSearchForm($request);
		$advancedSearchData = $advancedSearchForm->getData();

		$quickSearchForm = $this->createForm(new QuickSearchFormType());
		$quickSearchForm->handleRequest($request);
		$quickSearchData = $quickSearchForm->getData();

		$isAdvancedSearchFormSubmitted = $advancedSearchFacade->isAdvancedSearchFormSubmitted($request);
		if ($isAdvancedSearchFormSubmitted) {
			$queryBuilder = $advancedSearchFacade->getQueryBuilderByAdvancedSearchData($advancedSearchData);
		} else {
			$queryBuilder = $productListAdminFacade->getQueryBuilderByQuickSearchData($quickSearchData);
		}

		$dataSource = new QueryBuilderDataSource($queryBuilder, 'p.id');

		$grid = $gridFactory->create('productList', $dataSource);
		$grid->allowPaging();
		$grid->setDefaultOrder('name');

		$grid->addColumn('visible', 'p.visible', 'Viditelnost', true)->setClassAttribute('table-col table-col-10');
		$grid->addColumn('name', 'pt.name', 'Název', true);
		$grid->addColumn('price', 'p.price', 'Cena', true)->setClassAttribute('text-right');

		$grid->setActionColumnClassAttribute('table-col table-col-10');
		$grid->addActionColumn('edit', 'Upravit', 'admin_product_edit', ['id' => 'p.id']);
		$grid->addActionColumn('delete', 'Smazat', 'admin_product_delete', ['id' => 'p.id'])
			->setConfirmMessage('Opravdu chcete odstranit toto zboží?');

		$grid->setTheme('@SS6Shop/Admin/Content/Product/listGrid.html.twig');

		$administratorGridFacade->restoreAndRememberGridLimit($administrator, $grid);

		return $this->render('@SS6Shop/Admin/Content/Product/list.html.twig', [
			'gridView' => $grid->createView(),
			'quickSearchForm' => $quickSearchForm->createView(),
			'advancedSearchForm' => $advancedSearchForm->createView(),
			'isAdvancedSearchFormSubmitted' => $advancedSearchFacade->isAdvancedSearchFormSubmitted($request),
		]);
	}

	/**
	 * @Route("/product/delete/{id}", requirements={"id" = "\d+"})
	 * @param int $id
	 */
	public function deleteAction($id) {
		$flashMessageSender = $this->get('ss6.shop.flash_message.sender.admin');
		/* @var $flashMessageSender \SS6\ShopBundle\Model\FlashMessage\FlashMessageSender */
		$productEditFacade = $this->get('ss6.shop.product.product_edit_facade');
		/* @var $productEditFacade \SS6\ShopBundle\Model\Product\ProductEditFacade */

		try {
			$productName = $productEditFacade->getById($id)->getName();
			$productEditFacade->delete($id);

			$flashMessageSender->addSuccessFlashTwig('Produkt <strong>{{ name }}</strong> byl smazán', [
				'name' => $productName,
			]);
		} catch (\SS6\ShopBundle\Model\Product\Exception\ProductNotFoundException $ex) {
			$flashMessageSender->addErrorFlash('Zvolený produkt neexistuje.');
		}

		return $this->redirect($this->generateUrl('admin_product_list'));
	}

	/**
	 * @Route("/product/get-advanced-search-rule-form/", methods={"post"})
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 */
	public function getRuleFormAction(Request $request) {
		$advancedSearchFacade = $this->get('ss6.shop.advanced_search.advanced_search_facade');
		/* @var $advancedSearchFacade \SS6\ShopBundle\Model\AdvancedSearch\AdvancedSearchFacade */

		$ruleForm = $advancedSearchFacade->createRuleForm($request->get('filterName'), $request->get('newIndex'));

		return $this->render('@SS6Shop/Admin/Content/Product/AdvancedSearch/ruleForm.html.twig', [
			'rulesForm' => $ruleForm->createView(),
		]);
	}
}
