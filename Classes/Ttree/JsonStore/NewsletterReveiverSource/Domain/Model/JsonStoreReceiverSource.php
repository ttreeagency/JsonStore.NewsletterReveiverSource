<?php
/**
 * Created by IntelliJ IDEA.
 * User: sebastian
 * Date: 27.05.15
 * Time: 13:15
 */

namespace Ttree\JsonStore\NewsletterReveiverSource\Domain\Model;

use Sandstorm\Newsletter\Domain\Model\ReceiverSource;
use Ttree\JsonStore\Domain\Model\Document;
use Ttree\JsonStore\Service\StoreService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Utility\Files;

/**
 * @Flow\Entity
 */
class JsonStoreReceiverSource extends ReceiverSource
{
    /**
     * @var StoreService
     * @Flow\Inject
     */
    protected $store;

    /**
     * @var string
     */
    protected $documentType;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var string
     * @Flow\InjectConfiguration(path="receiverGroupCache", package="Sandstorm.Newsletter")
     * @Flow\Transient
     */
    protected $receiverGroupCache;

    public function getType()
    {
        return 'TtreeJsonStore';
    }

    public function initializeOrUpdate() {
        $output = array();
        $offset = 0;
        $documentCount = $this->store->count($this->documentType);
        $limit = 10;
        $pages = ceil($documentCount / $limit);
        while ($offset < $pages) {
            /** @var Document $document */
            foreach ($this->store->paginate($this->documentType, $offset, $limit) as $document) {
                $data = $document->getData();
                if (!isset($data['email'])) {
                    continue;
                }
                $output[md5($data['email'])] = json_encode($data);
            }
            $offset++;
        }

        Files::createDirectoryRecursively(dirname($this->getSourceFileName()));

        file_put_contents($this->getSourceFileName(), implode("\n", $output));

        parent::initializeOrUpdate();
    }

    /**
     * @return string
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * @param string $documentType
     */
    public function setDocumentType($documentType)
    {
        $this->documentType = $documentType;
    }

    public function getConfigurationAsString()
    {
        // TODO: Implement getConfigurationAsString() method.
    }

    public function getSourceFileName()
    {
        return $this->receiverGroupCache . '/_TTREEJSONSTORE_' . $this->persistenceManager->getIdentifierByObject($this);
    }
}
