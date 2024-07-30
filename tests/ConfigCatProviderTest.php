<?php

declare(strict_types=1);

namespace ConfigCat\OpenFeature\Test;

use ConfigCat\ClientOptions;
use ConfigCat\Log\LogLevel;
use ConfigCat\OpenFeature\ConfigCatProvider;
use ConfigCat\Override\FlagOverrides;
use ConfigCat\Override\OverrideBehaviour;
use ConfigCat\Override\OverrideDataSource;
use OpenFeature\implementation\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\Reason;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ConfigCatProviderTest extends TestCase
{
    private ConfigCatProvider $provider;
    private EvaluationContext $evaluationContext;

    public function setUp(): void
    {
        $config = [
            ClientOptions::LOG_LEVEL => LogLevel::NO_LOG,
            ClientOptions::FLAG_OVERRIDES => new FlagOverrides(
                OverrideDataSource::localFile('./tests/data/test_json_complex.json'),
                OverrideBehaviour::LOCAL_ONLY,
            ),
        ];

        $this->provider = new ConfigCatProvider('local', $config);

        $this->provider->setLogger(new NullLogger());

        $this->evaluationContext = new EvaluationContext('example@matching.com');
    }

    public function testCanBeInstantiated(): void
    {
        // Given
        $config = [
            ClientOptions::LOG_LEVEL => LogLevel::NO_LOG,
            ClientOptions::FLAG_OVERRIDES => new FlagOverrides(
                OverrideDataSource::localArray([]),
                OverrideBehaviour::LOCAL_ONLY,
            ),
        ];

        // When
        $instance = new ConfigCatProvider('local', $config);

        // Then
        $this->assertInstanceOf(Provider::class, $instance);
    }

    public function testCanResolveBoolean(): void
    {
        // Given
        $expectedValue = true;
        $expectedVariant = 'v-enabled';

        // When
        $actualDetails = $this->provider->resolveBooleanValue('enabledFeature', false);

        // Then
        $this->assertEquals($expectedValue, $actualDetails->getValue());
        $this->assertEquals($expectedVariant, $actualDetails->getVariant());
        $this->assertEquals(Reason::DEFAULT, $actualDetails->getReason());
    }

    public function testCanResolveString(): void
    {
        // Given
        $expectedValue = 'test';
        $expectedVariant = 'v-string';

        // When
        $actualDetails = $this->provider->resolveStringValue('stringSetting', '');

        // Then
        $this->assertEquals($expectedValue, $actualDetails->getValue());
        $this->assertEquals($expectedVariant, $actualDetails->getVariant());
        $this->assertEquals(Reason::DEFAULT, $actualDetails->getReason());
    }

    public function testCanResolveInt(): void
    {
        // Given
        $expectedValue = 5;
        $expectedVariant = 'v-int';

        // When
        $actualDetails = $this->provider->resolveIntegerValue('intSetting', 0);

        // Then
        $this->assertEquals($expectedValue, $actualDetails->getValue());
        $this->assertEquals($expectedVariant, $actualDetails->getVariant());
        $this->assertEquals(Reason::DEFAULT, $actualDetails->getReason());
    }

    public function testCanResolveFloat(): void
    {
        // Given
        $expectedValue = 1.2;
        $expectedVariant = 'v-double';

        // When
        $actualDetails = $this->provider->resolveFloatValue('doubleSetting', 0.0);

        // Then
        $this->assertEquals($expectedValue, $actualDetails->getValue());
        $this->assertEquals($expectedVariant, $actualDetails->getVariant());
        $this->assertEquals(Reason::DEFAULT, $actualDetails->getReason());
    }

    public function testCanResolveWithTargeting(): void
    {
        // Given
        $expectedValue = true;
        $expectedVariant = 'v-disabled-t';

        // When
        $actualDetails = $this->provider->resolveBooleanValue('disabledFeature', false, $this->evaluationContext);

        // Then
        $this->assertEquals($expectedValue, $actualDetails->getValue());
        $this->assertEquals($expectedVariant, $actualDetails->getVariant());
        $this->assertEquals(Reason::TARGETING_MATCH, $actualDetails->getReason());
    }

    public function testFlagKeyNotFound(): void
    {
        // Given
        $defaultValue = false;

        // When
        $actualDetails = $this->provider->resolveBooleanValue('non-existing', $defaultValue, $this->evaluationContext);

        // Then
        $this->assertEquals($defaultValue, $actualDetails->getValue());
        $this->assertEquals(ErrorCode::FLAG_NOT_FOUND(), $actualDetails->getError()?->getResolutionErrorCode());
        $this->assertEquals(Reason::ERROR, $actualDetails->getReason());
        $this->assertEquals("Failed to evaluate setting 'non-existing' (the key was not found in config JSON). Returning the `defaultValue` parameter that you specified in your application: 'false'. Available keys: ['disabledFeature', 'enabledFeature', 'intSetting', 'doubleSetting', 'stringSetting'].", $actualDetails->getError()?->getResolutionErrorMessage());
    }

    public function testFlagTypeMismatch(): void
    {
        // Given
        $defaultValue = false;

        // When
        $actualDetails = $this->provider->resolveBooleanValue('stringSetting', $defaultValue, $this->evaluationContext);

        // Then
        $this->assertEquals($defaultValue, $actualDetails->getValue());
        $this->assertEquals(Reason::ERROR, $actualDetails->getReason());
        $this->assertEquals(ErrorCode::TYPE_MISMATCH(), $actualDetails->getError()?->getResolutionErrorCode());
    }
}
