<?php

namespace Drupal\commerce_invoice\Form;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\File\MimeType\MimeTypeGuesser;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pdf_tools\PDFGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InvoiceGenerateForm extends FormBase {

  /**
   * The commerce order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The pdf generator.
   *
   * @var \Drupal\pdf_tools\PDFGeneratorInterface
   */
  protected $pdfGenerator;

  /**
   * The order view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * The file entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * The file mimetype guesser.
   *
   * @var \Drupal\Core\File\MimeType\MimeTypeGuesser
   */
  protected $mimeTypeGuesser;

  /**
   * InvoiceGenerateForm constructor.
   *
   * @param \Drupal\pdf_tools\PDFGeneratorInterface $pdf_generator
   */
  public function __construct(
    PDFGeneratorInterface $pdf_generator,
    EntityViewBuilderInterface $view_builder,
    EntityStorageInterface $file_storage,
    MimeTypeGuesser $mime_type_guesser
  ) {
    $this->pdfGenerator = $pdf_generator;
    $this->viewBuilder = $view_builder;
    $this->fileStorage = $file_storage;
    $this->mimeTypeGuesser = $mime_type_guesser;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pdf_tools.generator'),
      $container->get('entity_type.manager')->getViewBuilder('commerce_order'),
      $container->get('entity_type.manager')->getStorage('file'),
      $container->get('file.mime_type.guesser')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'commerce_invoice_generate_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
    $this->order = $order;

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
      '#ajax' => [
        'callback' => [$this, 'downloadFileAjaxCallback'],
      ]
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $options = [
      '__destination' => 'private://invoices/invoice_'.$this->order->getOrderNumber().'.pdf',
    ];
    $uri = $this->pdfGenerator->generateFromHTML(
      render($this->viewBuilder->view($this->order, 'invoice')),
      $options
    );

    $file = $this->fileStorage->create([
      'uri' => $uri,
      'size' => filesize($uri),
      'uid' => \Drupal::currentUser()->id(),
      'status' => 1,
      'filename' => basename($uri),
      'filemime' => $this->mimeTypeGuesser->guess($uri),
    ]);
    $file->save();

    $form_state->set('file', $file);
  }

  /**
   *
   */
  public function downloadFileAjaxCallback(array $form, FormStateInterface $form_state) {
  }
}
