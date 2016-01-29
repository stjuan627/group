<?php
/**
 * @file
 * Contains \Drupal\group\Entity\GroupContent.
 */

namespace Drupal\group\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;

// @todo Remove the below https://www.drupal.org/node/2645136 lands.
use Drupal\Core\Url;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;


/**
 * Defines the Group content entity.
 *
 * @ingroup group
 *
 * @ContentEntityType(
 *   id = "group_content",
 *   label = @Translation("Group content entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\group\Entity\Controller\GroupContentListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\group\Entity\Routing\GroupContentRouteProvider",
 *     },
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupContentForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupContentForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupContentDeleteForm",
 *     },
 *     "access" = "Drupal\group\Entity\Access\GroupContentAccessControlHandler",
 *   },
 *   base_table = "group_content",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "label",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *   },
 *   bundle_entity_type = "group_content_type",
 *   field_ui_base_route = "entity.group_content_type.edit_form",
 *   permission_granularity = "bundle"
 * )
 */
class GroupContent extends ContentEntityBase implements GroupContentInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getGroupContentType() {
    return $this->type->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->gid->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity_id->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getGroupContentType()->getContentPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getPlugin()->getContentLabel($this);
  }

  /**
   * {@inheritdoc}
   *
   * Exact copy of Entity::toUrl() with the exception of one line until the
   * patch in https://www.drupal.org/node/2645136 lands.
   *
   * @todo Remove this if the issue above gets resolved.
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    if ($this->id() === NULL) {
      throw new EntityMalformedException(sprintf('The "%s" entity cannot have a URI as it does not have an ID', $this->getEntityTypeId()));
    }

    // The links array might contain URI templates set in annotations.
    $link_templates = $this->linkTemplates();

    // Links pointing to the current revision point to the actual entity. So
    // instead of using the 'revision' link, use the 'canonical' link.
    if ($rel === 'revision' && $this instanceof RevisionableInterface && $this->isDefaultRevision()) {
      $rel = 'canonical';
    }

    if (isset($link_templates[$rel])) {
      $route_parameters = $this->urlRouteParameters($rel);
      $route_name = $this->urlRoute($rel);
      $uri = new Url($route_name, $route_parameters);
    }
    else {
      $bundle = $this->bundle();
      // A bundle-specific callback takes precedence over the generic one for
      // the entity type.
      $bundles = $this->entityManager()->getBundleInfo($this->getEntityTypeId());
      if (isset($bundles[$bundle]['uri_callback'])) {
        $uri_callback = $bundles[$bundle]['uri_callback'];
      }
      elseif ($entity_uri_callback = $this->getEntityType()->getUriCallback()) {
        $uri_callback = $entity_uri_callback;
      }

      // Invoke the callback to get the URI. If there is no callback, use the
      // default URI format.
      if (isset($uri_callback) && is_callable($uri_callback)) {
        $uri = call_user_func($uri_callback, $this);
      }
      else {
        throw new UndefinedLinkTemplateException("No link template '$rel' found for the '{$this->getEntityTypeId()}' entity type");
      }
    }

    // Pass the entity data through as options, so that alter functions do not
    // need to look up this entity again.
    $uri
      ->setOption('entity_type', $this->getEntityTypeId())
      ->setOption('entity', $this);

    // Display links by default based on the current language.
    if ($rel !== 'collection') {
      $options += ['language' => $this->language()];
    }

    $uri_options = $uri->getOptions();
    $uri_options += $options;

    return $uri->setOptions($uri_options);
  }

  /**
   * {@inheritdoc}
   */
  protected function linkTemplates() {
    // @todo Look into this: What with custom templates? Plugin ::getPaths()?
    return [
      'collection' => $this->getPlugin()->getPath('collection'),
      'canonical' => $this->getPlugin()->getPath('canonical'),
      'add-form' => $this->getPlugin()->getPath('add-form'),
      'edit-form' => $this->getPlugin()->getPath('edit-form'),
      'delete-form' => $this->getPlugin()->getPath('delete-form'),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @todo Will inherit docs once https://www.drupal.org/node/2645136 lands.
   */
  protected function urlRoute($rel) {
    $route_prefix = 'entity.group_content.' . str_replace(':', '__', $this->getPlugin()->getPluginId());
    return $route_prefix . '.' . str_replace(array('-', 'drupal:'), array('_', ''), $rel);
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['group'] = $this->gid->entity->id();
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Group content ID'))
      ->setDescription(t('The ID of the Group content entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Group content entity.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The group content type.'))
      ->setSetting('target_type', 'group_content_type')
      ->setReadOnly(TRUE);

    $fields['gid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent group'))
      ->setDescription(t('The group containing the entity.'))
      ->setSetting('target_type', 'group')
      ->setReadOnly(TRUE);

    // Borrowed this logic from the Comment module.
    // Warning! May change in the future: https://www.drupal.org/node/2346347
    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Content'))
      ->setDescription(t('The entity to add to the group.'))
      ->addConstraint('GroupContentCardinality')
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The group content language code.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'hidden',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 2,
      ));

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setReadOnly(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed on'))
      ->setDescription(t('The time that the group was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    // Borrowed this logic from the Comment module.
    // Warning! May change in the future: https://www.drupal.org/node/2346347
    if ($group_content_type = GroupContentType::load($bundle)) {
      $plugin = $group_content_type->getContentPlugin();

      $fields['entity_id'] = clone $base_field_definitions['entity_id'];
      foreach ($plugin->getEntityReferenceSettings() as $name => $setting) {
        $fields['entity_id']->setSetting($name, $setting);
      }

      return $fields;
    }

    return [];
  }

}
