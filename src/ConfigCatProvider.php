<?php

declare(strict_types=1);

namespace ConfigCat\OpenFeature;

use ConfigCat\ClientInterface;
use ConfigCat\ConfigCatClient;
use ConfigCat\EvaluationDetails;
use ConfigCat\User;
use DateTime;
use InvalidArgumentException;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\common\Metadata;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Psr\Log\LoggerInterface;

use const FILTER_VALIDATE_BOOLEAN;

class ConfigCatProvider implements Provider
{
    protected const NAME = 'ConfigCatProvider';

    private ClientInterface $client;

    /**
     * Creates a new ConfigCatProvider.
     *
     * @see https://configcat.com/docs/sdk-reference/php/#creating-the-configcat-client Documentation of ConfigCatProvider SDK configuration options
     *
     * @param string  $sdkKey  the SDK Key used to communicate with the ConfigCatProvider services
     * @param mixed[] $options ConfigCatProvider SDK configuration options
     *
     * @throws InvalidArgumentException if the $sdkKey is not valid
     */
    public function __construct(string $sdkKey, array $options = [])
    {
        $this->client = new ConfigCatClient($sdkKey, $options);
    }

    /**
     * IMPORTANT: Changing the logger here does not affect the internal ConfigCat SDK's logging.
     * To change the SDK's logger, you have to use the `$options` argument of the `ConfigCatProvider` constructor.
     */
    public function setLogger(LoggerInterface $logger): void {}

    public function getHooks(): array
    {
        return [];
    }

    public function getMetadata(): Metadata
    {
        return new ConfigCatMetadata();
    }

    public function resolveBooleanValue(string $flagKey, bool $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        $user = $this->contextToUser($context);
        $details = $this->client->getValueDetails($flagKey, $defaultValue, $user);

        $value = $details->getValue();
        if (!\is_bool($value)) {
            $builder = new ResolutionDetailsBuilder();
            $builder->withValue($defaultValue);
            $builder->withError(new ResolutionError(ErrorCode::TYPE_MISMATCH()));

            return $builder->build();
        }

        return $this->produceResolutionDetails(FlagValueType::BOOLEAN, $value, $defaultValue, $details);
    }

    public function resolveStringValue(string $flagKey, string $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        $user = $this->contextToUser($context);
        $details = $this->client->getValueDetails($flagKey, $defaultValue, $user);

        $value = $details->getValue();
        if (!\is_string($value)) {
            $builder = new ResolutionDetailsBuilder();

            return $builder->withValue($defaultValue)
                ->withError(new ResolutionError(ErrorCode::TYPE_MISMATCH()))
                ->build()
            ;
        }

        return $this->produceResolutionDetails(FlagValueType::STRING, $value, $defaultValue, $details);
    }

    public function resolveIntegerValue(string $flagKey, int $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        $user = $this->contextToUser($context);
        $details = $this->client->getValueDetails($flagKey, $defaultValue, $user);

        $value = $details->getValue();
        if (!\is_int($value)) {
            $builder = new ResolutionDetailsBuilder();

            return $builder->withValue($defaultValue)
                ->withError(new ResolutionError(ErrorCode::TYPE_MISMATCH()))
                ->build()
            ;
        }

        return $this->produceResolutionDetails(FlagValueType::INTEGER, $value, $defaultValue, $details);
    }

    public function resolveFloatValue(string $flagKey, float $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        $user = $this->contextToUser($context);
        $details = $this->client->getValueDetails($flagKey, $defaultValue, $user);

        $value = $details->getValue();
        if (!\is_float($value)) {
            $builder = new ResolutionDetailsBuilder();

            return $builder->withValue($defaultValue)
                ->withError(new ResolutionError(ErrorCode::TYPE_MISMATCH()))
                ->build()
            ;
        }

        return $this->produceResolutionDetails(FlagValueType::FLOAT, $value, $defaultValue, $details);
    }

    /**
     * @param mixed[] $defaultValue
     */
    public function resolveObjectValue(string $flagKey, array $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        $user = $this->contextToUser($context);
        $details = $this->client->getValueDetails($flagKey, $defaultValue, $user);

        $value = $details->getValue();
        if (!\is_string($value)) {
            $builder = new ResolutionDetailsBuilder();

            return $builder->withValue($defaultValue)
                ->withError(new ResolutionError(ErrorCode::TYPE_MISMATCH()))
                ->build()
            ;
        }

        return $this->produceResolutionDetails(FlagValueType::OBJECT, $value, $defaultValue, $details);
    }

    private function contextToUser(?EvaluationContext $context = null): ?User
    {
        if (\is_null($context)) {
            return null;
        }

        /** @var null|string $email */
        $email = null;

        /** @var null|string $email */
        $country = null;

        $emailAttr = $context->getAttributes()->get(User::EMAIL_ATTRIBUTE);
        if (\is_string($emailAttr)) {
            $email = $emailAttr;
        }

        $countryAttr = $context->getAttributes()->get(User::COUNTRY_ATTRIBUTE);
        if (\is_string($countryAttr)) {
            $country = $countryAttr;
        }

        return new User($context->getTargetingKey() ?? '', $email, $country, $context->getAttributes()->toArray());
    }

    /**
     * @param bool|DateTime|float|int|mixed[]|string $defaultValue
     */
    private function produceResolutionDetails(
        string $flagType,
        bool|float|int|string $value,
        array|bool|DateTime|float|int|string $defaultValue,
        EvaluationDetails $evaluationDetails,
    ): ResolutionDetails {
        $error = $evaluationDetails->getErrorMessage();
        if (!\is_null($error)) {
            $builder = new ResolutionDetailsBuilder();
            $builder->withValue($defaultValue);
            if (\str_contains($error, 'key was not found in config JSON')) {
                $builder->withError(new ResolutionError(ErrorCode::FLAG_NOT_FOUND(), $error));
            } else {
                $builder->withError(new ResolutionError(ErrorCode::GENERAL(), $error));
            }

            return $builder->build();
        }

        $result = $this->extractValueFromDetails($flagType, $value, $defaultValue);
        $variant = $evaluationDetails->getVariationId();
        if (!\is_null($variant)) {
            $result->withVariant($variant);
        }

        return $result->build();
    }

    /**
     * @param bool|DateTime|float|int|mixed[]|string $defaultValue
     */
    private function extractValueFromDetails(
        string $flagType,
        bool|float|int|string $value,
        array|bool|DateTime|float|int|string $defaultValue,
    ): ResolutionDetailsBuilder {
        $builder = new ResolutionDetailsBuilder();

        return match ($flagType) {
            FlagValueType::BOOLEAN => $builder->withValue(\filter_var($value, FILTER_VALIDATE_BOOLEAN)),
            FlagValueType::FLOAT => $builder->withValue((float) $value),
            FlagValueType::INTEGER => $builder->withValue((int) $value),
            FlagValueType::OBJECT => $this->extractObjectValue($value, $defaultValue, $builder),
            FlagValueType::STRING => $builder->withValue($value),
            default => $builder->withValue($defaultValue)->withError(new ResolutionError(ErrorCode::TYPE_MISMATCH())),
        };
    }

    /**
     * @param bool|DateTime|float|int|mixed[]|string $defaultValue
     */
    private function extractObjectValue(
        bool|float|int|string $value,
        array|bool|DateTime|float|int|string $defaultValue,
        ResolutionDetailsBuilder $builder
    ): ResolutionDetailsBuilder {
        if (\is_string($value)) {
            /** @var mixed[] $decoded */
            $decoded = \json_decode($value, true);

            return $builder->withValue($decoded);
        }

        return $builder->withValue($defaultValue)->withError(new ResolutionError(ErrorCode::TYPE_MISMATCH()));
    }
}
