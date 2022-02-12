<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace SpaethTech\UCRM\SDK\Data\Models;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use SpaethTech\UCRM\SDK\Data\Models\Model;
use SpaethTech\UCRM\SDK\Data\Annotations\TableNameAnnotation as TableName;
use SpaethTech\UCRM\SDK\Data\Annotations\ColumnNameAnnotation as ColumnName;

/**
 * Class AppKey
 *
 * @package UCRM\UNMS
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 * @final
 *
 * @TableName ucrm.app_key
 *
 * @method int|null                 getId()
 * @method string|null              getName()
 * @method AppKey                   setName(string $name)
 * @method string|null              getKey()
 * @method AppKey                   setKey(string $key)
 * @method string|null              getType()
 * @method AppKey                   setType(string $type)
 * @see    DateTimeInterface|null   getCreatedDate()
 * @see    AppKey                   setCreatedDate(\DateTimeInterface $timestamp)
 * @see    DateTimeInterface|null   getLastUsedDate()
 * @see    AppKey                   setLastUsedDate(\DateTimeInterface $timestamp)
 * @method int|null                 getPluginId()
 * @method AppKey                   setPluginId(int $pluginId)
 * @see    DateTimeInterface|null   getDeletedAt()
 * @see    AppKey                   setDeletedAt(\DateTimeInterface $timestamp)
 */
final class AppKey extends Model
{
    private const TIMESTAMP_FORMAT = "Y-m-d H:i:s";
    
    /**
     * @var int
     * @ColumnName key_id
     */
    protected $keyId;
    
    /**
     * @var string
     */
    protected $name;
    /** @noinspection PhpUnused */
    
    /**
     * @var string
     */
    protected $key;
    
    /**
     * @var string
     */
    protected $type;
    
    /**
     * @var DateTime
     * @ColumnName created_date
     */
    protected $createdDate;
    
    /**
     * @return DateTimeInterface|null
     * @throws Exception
     */
    public function getCreatedDate(): ?DateTimeInterface
    {
        return $this->createdDate !== null ? new DateTimeImmutable($this->createdDate) : null;
    }
    
    /**
     * @param DateTimeInterface|null $timestamp
     * @return AppKey
     */
    public function setCreatedDate(?DateTimeInterface $timestamp): self
    {
        $this->createdDate = $timestamp !== null ? $timestamp->format(self::TIMESTAMP_FORMAT) : null;
        return $this;
    }
    
    /**
     * @var DateTime
     * @ColumnName last_used_date
     */
    protected $lastUsedDate;
    
    /**
     * @return DateTimeInterface|null
     * @throws Exception
     */
    public function getLastUsedDate(): ?DateTimeInterface
    {
        return $this->lastUsedDate !== null ? new DateTimeImmutable($this->lastUsedDate) : null;
    }
    
    /**
     * @param DateTimeInterface|null $timestamp
     * @return AppKey
     */
    public function setLastUsedDate(?DateTimeInterface $timestamp): self
    {
        $this->lastUsedDate = $timestamp !== null ? $timestamp->format(self::TIMESTAMP_FORMAT) : null;
        return $this;
    }
    
    /**
     * @var int
     * @ColumnName plugin_id
     */
    protected $pluginId;
    
    /**
     * @var DateTime
     * @ColumnName deleted_at
     */
    protected $deletedAt;
    
    /**
     * @return DateTimeInterface|null
     * @throws Exception
     */
    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt !== null ? new DateTimeImmutable($this->deletedAt) : null;
    }
    
    /**
     * @param DateTimeInterface|null $timestamp
     * @return AppKey
     */
    public function setDeletedAt(?DateTimeInterface $timestamp): self
    {
        $this->deletedAt = $timestamp !== null ? $timestamp->format(self::TIMESTAMP_FORMAT) : null;
        return $this;
    }
    
}
