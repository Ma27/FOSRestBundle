<?php

/*
 * This file is part of the FOSRestBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\RestBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Checks if a serializer is either set or can be auto-configured.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 * @author Florian Voutzinos <florian@voutzinos.com>
 *
 * @internal
 */
class SerializerConfigurationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->has('fos_rest.serializer')) {
            $class = $container->getParameterBag()->resolveValue(
                $container->findDefinition('fos_rest.serializer')->getClass()
            );
            if (!is_subclass_of($class, 'FOS\RestBundle\Serializer\Serializer')) {
                @trigger_error('Support of custom serializers is deprecated since 1.8 and will be dropped in 2.0. You should create a new class implementing FOS\RestBundle\Serializer\Serializer and define it as fos_rest.serializer', E_USER_DEPRECATED);

                // @todo: Uncomment in 2.0
                // throw new \InvalidArgumentException(sprintf('"fos_rest.serializer" must implement FOS\RestBundle\Serializer\Serializer (instance of "%s" given).', $class));
            }

            return;
        }

        if (!$container->has('serializer') && !$container->has('jms_serializer.serializer')) {
            throw new \InvalidArgumentException('Neither a service called "jms_serializer.serializer" nor "serializer" is available and no serializer is explicitly configured. You must either enable the JMSSerializerBundle, enable the FrameworkBundle serializer or configure a custom serializer.');
        }

        if ($container->has('jms_serializer.serializer')) {
            $container->setAlias('fos_rest.serializer', 'fos_rest.serializer.jms');
        } else {
            $container->removeDefinition('fos_rest.serializer.exception_wrapper_serialize_handler');
        }

        if ($container->has('serializer')) {
            $class = $container->getParameterBag()->resolveValue(
                $container->findDefinition('serializer')->getClass()
            );

            if (is_subclass_of($class, 'Symfony\Component\Serializer\SerializerInterface')) {
                $container->setAlias('fos_rest.serializer', 'fos_rest.serializer.symfony');
            } elseif (is_subclass_of($class, 'JMS\Serializer\SerializerInterface')) {
                $container->setAlias('fos_rest.serializer', 'fos_rest.serializer.jms');
            } else {
                @trigger_error('Support of custom serializers is deprecated since 1.8 and will be dropped in 2.0. You should create a new class implementing FOS\RestBundle\Serializer\Serializer and define it as fos_rest.serializer', E_USER_DEPRECATED);
                $container->setAlias('fos_rest.serializer', 'serializer');

                // @todo: Uncomment in 2.0
                // throw new \InvalidArgumentException(sprintf('"fos_rest.serializer" must implement FOS\RestBundle\Serializer\Serializer (instance of "%s" given).', $class));
            }
        } else {
            $container->removeDefinition('fos_rest.serializer.exception_wrapper_normalizer');
        }
    }
}
