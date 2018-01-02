<?php

namespace Mathielen\ImportEngine\ValueObject;

use Ddeboer\DataImport\Result;

class ImportRun
{
    const STATE_REVOKED = 'revoked';
    const STATE_FINISHED = 'finished';
    const STATE_CREATED = 'created';
    const STATE_VALIDATED = 'validated';

    protected $id;

    /**
     * @var ImportConfiguration
     */
    protected $configuration;

    /**
     * @var \DateTime
     */
    protected $createdAt;
    protected $createdBy;

    /**
     * @var \DateTime
     */
    protected $validatedAt;
    protected $validationMessages;

    /**
     * @var \DateTime
     */
    protected $finishedAt;

    /**
     * @var \DateTime
     */
    protected $revokedAt;
    protected $revokedBy;

    protected $statistics = array();
    protected $info = array();

    /**
     * @var Result
     */
    protected $result;

    /**
     * arbitrary data.
     */
    protected $context;

    public function __construct(ImportConfiguration $configuration = null, $createdBy = null)
    {
        $this->id = uniqid();
        $this->createdAt = new \DateTime();
        $this->configuration = $configuration;
        $this->createdBy = $createdBy;
    }

    /**
     * @return ImportRun
     */
    public static function create(ImportConfiguration $configuration = null, $createdBy = null)
    {
        return new self($configuration, $createdBy);
    }

    /**
     * @return ImportRun
     */
    public function setContext($context)
    {
        //merge existing context with new one, if both are arrays
        if (is_array($context) && is_array($this->context)) {
            $context = array_merge($this->context, $context);
        }

        $this->context = $context;

        return $this;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @return ImportRun
     */
    public function revoke($revokedBy = null)
    {
        if (!$this->isFinished()) {
            throw new \LogicException('Cannot revoke import if not already finished.');
        }

        $this->revokedAt = new \DateTime();
        $this->revokedBy = $revokedBy;

        return $this;
    }

    /**
     * @return ImportRun
     */
    public function reset()
    {
        $this->finishedAt = null;

        return $this;
    }

    public function isRevoked()
    {
        return $this->getState() == self::STATE_REVOKED;
    }

    /**
     * @return ImportRun
     */
    public function finish()
    {
        $this->finishedAt = new \DateTime();

        return $this;
    }

    public function isFinished()
    {
        return $this->getState() == self::STATE_FINISHED;
    }

    /**
     * @return ImportRun
     */
    public function validated(array $validationMessages = null)
    {
        $this->validatedAt = empty($this->validatedAt) ? new \DateTime() : $this->validatedAt;
        $this->validationMessages = $validationMessages;

        return $this;
    }

    public function getValidationMessages()
    {
        return $this->validationMessages;
    }

    public function isValidated()
    {
        return $this->isFinished() || $this->getState() == self::STATE_VALIDATED;
    }

    public function isRunnable()
    {
        return !$this->isFinished() && !$this->isRevoked();
    }

    /**
     * @return ImportConfiguration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return ImportRun
     */
    public function setStatistics(array $statistics)
    {
        $this->statistics = $statistics;

        return $this;
    }

    public function getStatistics()
    {
        return $this->statistics;
    }

    /**
     * @return ImportRun
     */
    public function setInfo(array $info)
    {
        $this->info = $info;

        return $this;
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function getState()
    {
        return self::timestampsToState($this->revokedAt, $this->finishedAt, $this->validatedAt);
    }

    public static function timestampsToState($revokedAt, $finishedAt, $validatedAt)
    {
        if (!empty($revokedAt)) {
            return self::STATE_REVOKED;
        }
        if (!empty($finishedAt)) {
            return self::STATE_FINISHED;
        }
        if (!empty($validatedAt)) {
            return self::STATE_VALIDATED;
        }

        return self::STATE_CREATED;
    }

    public function toArray()
    {
        return array(
            'id' => $this->id,
            'configuration' => $this->configuration ? $this->configuration->toArray() : null,
            'statistics' => $this->statistics,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt->getTimestamp(),
            'revoked_by' => $this->revokedBy,
            'revoked_at' => $this->revokedAt ? $this->revokedAt->getTimestamp() : null,
            'finished_at' => $this->finishedAt ? $this->finishedAt->getTimestamp() : null,
            'state' => $this->getState(),
        );
    }

    public function setResult(Result $result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return Result
     */
    public function getResult()
    {
        return $this->result;
    }

}
