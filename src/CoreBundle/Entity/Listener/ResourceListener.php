<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceToCourseInterface;
use Chamilo\CoreBundle\Entity\ResourceToRootInterface;
use Chamilo\CoreBundle\Entity\ResourceWithUrlInterface;
use Chamilo\CoreBundle\Entity\UrlResourceInterface;
use Chamilo\CoreBundle\ToolChain;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

/**
 * Class ResourceListener.
 */
class ResourceListener
{
    protected $slugify;
    protected $request;
    protected $accessUrl;

    /**
     * ResourceListener constructor.
     */
    public function __construct(SlugifyInterface $slugify, ToolChain $toolChain, RequestStack $request, Security $security)
    {
        $this->slugify = $slugify;
        $this->security = $security;
        $this->toolChain = $toolChain;
        $this->request = $request;
        $this->accessUrl = null;
    }

    public function getAccessUrl($em)
    {
        if (null === $this->accessUrl) {
            $request = $this->request->getCurrentRequest();
            if (null === $request) {
                throw new \Exception('An Request is needed');
            }
            $sessionRequest = $request->getSession();

            if (null === $sessionRequest) {
                throw new \Exception('An Session is needed');
            }

            $id = $sessionRequest->get('access_url_id');
            $url = $em->getRepository('ChamiloCoreBundle:AccessUrl')->find($id);

            if ($url) {
                $this->accessUrl = $url;

                return $url;
            }
        }

        if (null === $this->accessUrl) {
            throw new \Exception('An AccessUrl is needed');
        }

        return $this->accessUrl;
    }

    public function prePersist(AbstractResource $resource, LifecycleEventArgs $args)
    {
        error_log('ResourceListener prePersist '.get_class($resource));
        $em = $args->getEntityManager();
        $request = $this->request;

        $url = null;
        if ($resource instanceof ResourceWithUrlInterface) {
            $url = $this->getAccessUrl($em);
            $resource->addUrl($url);
        }

        if ($resource->hasResourceNode()) {
            if ($resource instanceof ResourceToRootInterface) {
                $url = $this->getAccessUrl($em);
                $resource->getResourceNode()->setParent($url->getResourceNode());
            }

            // Do not override resource node already added.
            return true;
        }

        // Add resource node
        $creator = $this->security->getUser();
        $resourceNode = new ResourceNode();
        $resourceName = $resource->getResourceName();
        $extension = $this->slugify->slugify(pathinfo($resourceName, PATHINFO_EXTENSION));

        if (empty($extension)) {
            $slug = $this->slugify->slugify($resourceName);
        } else {
            $originalExtension = pathinfo($resourceName, PATHINFO_EXTENSION);
            $originalBasename = \basename($resourceName, $originalExtension);
            $slug = sprintf('%s.%s', $this->slugify->slugify($originalBasename), $originalExtension);
        }

        $repo = $em->getRepository('ChamiloCoreBundle:ResourceType');
        $class = str_replace('Entity', 'Repository', get_class($args->getEntity()));
        $class .= 'Repository';
        $name = $this->toolChain->getResourceTypeNameFromRepository($class);
        $resourceType = $repo->findOneBy(['name' => $name]);
        $resourceNode
            ->setTitle($resourceName)
            ->setSlug($slug)
            ->setCreator($creator)
            ->setResourceType($resourceType)
        ;

        if ($resource instanceof ResourceToRootInterface) {
            $url = $this->getAccessUrl($em);
            $resourceNode->setParent($url->getResourceNode());
        }

        if ($resource->hasParentResourceNode()) {
            $nodeRepo = $em->getRepository('ChamiloCoreBundle:ResourceNode');
            $parent = $nodeRepo->find($resource->getParentResourceNode());
            $resourceNode->setParent($parent);
        }

        if ($resource->hasResourceFile()) {
            /** @var File $uploadedFile */
            $uploadedFile = $request->getCurrentRequest()->files->get('resourceFile');
            $resourceFile = new ResourceFile();
            $resourceFile->setName($uploadedFile->getFilename());
            $resourceFile->setOriginalName($uploadedFile->getFilename());
            $resourceFile->setFile($uploadedFile);

            $em->persist($resourceFile);
            $resourceNode->setResourceFile($resourceFile);
        }

        if ($resource instanceof ResourceToCourseInterface) {
            //$this->request->getCurrentRequest()->getSession()->get('access_url_id');
            //$resourceNode->setParent($url->getResourceNode());
        }

        $resource->setResourceNode($resourceNode);
        $em->persist($resourceNode);

        return $resourceNode;
    }

    /**
     * When updating a Resource.
     */
    public function preUpdate(AbstractResource $resource, PreUpdateEventArgs $event)
    {
        /*error_log('preUpdate');
        error_log($fieldIdentifier);
        $em = $event->getEntityManager();
        if ($event->hasChangedField($fieldIdentifier)) {
            error_log('changed');
            $oldValue = $event->getOldValue($fieldIdentifier);
            error_log($oldValue);
            $newValue = $event->getNewValue($fieldIdentifier);
            error_log($newValue);
            //$this->updateResourceName($resource, $newValue, $em);
        }*/
    }

    public function postUpdate(AbstractResource $resource, LifecycleEventArgs $args)
    {
        //error_log('postUpdate');
        //$em = $args->getEntityManager();
        //$this->updateResourceName($resource, $resource->getResourceName(), $em);
    }

    public function updateResourceName(AbstractResource $resource, $newValue, $em)
    {
        // Updates resource node name with the resource name.
        /*$resourceNode = $resource->getResourceNode();

        $newName = $resource->getResourceName();

        $name = $resourceNode->getSlug();

        if ($resourceNode->hasResourceFile()) {
            $originalExtension = pathinfo($name, PATHINFO_EXTENSION);
            $originalBasename = \basename($name, $originalExtension);
            $modified = sprintf('%s.%s', $this->slugify->slugify($originalBasename), $originalExtension);
        } else {
            $modified = $this->slugify->slugify($name);
        }

        error_log($name);
        error_log($modified);

        $resourceNode->setSlug($modified);

        if ($resourceNode->hasResourceFile()) {
            $resourceNode->getResourceFile()->setOriginalName($name);
        }
        $em->persist($resourceNode);
        $em->flush();*/
    }
}
