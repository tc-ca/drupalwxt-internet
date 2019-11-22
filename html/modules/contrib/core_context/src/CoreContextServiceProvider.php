<?php

namespace Drupal\core_context;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\core_context\EventSubscriber\SectionComponentRenderArray;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class CoreContextServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');

    if (isset($modules['layout_builder'])) {
      $definition = new Definition(SectionComponentRenderArray::class);
      $definition->setArguments([
        new Reference('plugin.manager.layout_builder.section_storage'),
        new Reference('entity_type.manager'),
      ]);
      $definition->addTag('event_subscriber');
      $container->setDefinition('core_context.render_section_component_subscriber', $definition);
    }
  }

}
