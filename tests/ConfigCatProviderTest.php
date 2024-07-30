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
    private Provider $provider;
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

    public function testCanResolveBoolean(): void
    {
        $actualDetails = $this->provider->resolveBooleanValue('enabledFeature', false);

        $this->assertTrue($actualDetails->getValue());
        $this->assertEquals('v-enabled', $actualDetails->getVariant());
        $this->assertEquals(Reason::DEFAULT, $actualDetails->getReason());
    }

    public function testCanResolveString(): void
    {
        $actualDetails = $this->provider->resolveStringValue('stringSetting', '');

        $this->assertEquals('test', $actualDetails->getValue());
        $this->assertEquals('v-string', $actualDetails->getVariant());
        $this->assertEquals(Reason::DEFAULT, $actualDetails->getReason());
    }

    public function testCanResolveInt(): void
    {
        $actualDetails = $this->provider->resolveIntegerValue('intSetting', 0);

        $this->assertEquals(5, $actualDetails->getValue());
        $this->assertEquals('v-int', $actualDetails->getVariant());
        $this->assertEquals(Reason::DEFAULT, $actualDetails->getReason());
    }

    public function testCanResolveFloat(): void
    {
        $actualDetails = $this->provider->resolveFloatValue('doubleSetting', 0.0);

        $this->assertEquals(1.2, $actualDetails->getValue());
        $this->assertEquals('v-double', $actualDetails->getVariant());
        $this->assertEquals(Reason::DEFAULT, $actualDetails->getReason());
    }

    public function testCanResolveObject(): void
    {
        $actualDetails = $this->provider->resolveObjectValue('objectSetting', []);

        $this->assertEquals(['bool_field' => true, 'text_field' => 'value'], $actualDetails->getValue());
        $this->assertEquals('v-object', $actualDetails->getVariant());
        $this->assertEquals(Reason::DEFAULT, $actualDetails->getReason());
    }

    public function testCanResolveWithTargeting(): void
    {
        $actualDetails = $this->provider->resolveBooleanValue('disabledFeature', false, $this->evaluationContext);

        $this->assertTrue($actualDetails->getValue());
        $this->assertEquals('v-disabled-t', $actualDetails->getVariant());
        $this->assertEquals(Reason::TARGETING_MATCH, $actualDetails->getReason());
    }

    public function testFlagKeyNotFound(): void
    {
        $actualDetails = $this->provider->resolveBooleanValue('non-existing', false, $this->evaluationContext);

        $this->assertFalse($actualDetails->getValue());
        $this->assertEquals(ErrorCode::FLAG_NOT_FOUND(), $actualDetails->getError()?->getResolutionErrorCode());
        $this->assertEquals(Reason::ERROR, $actualDetails->getReason());
        $this->assertEquals("Failed to evaluate setting 'non-existing' (the key was not found in config JSON). Returning the `defaultValue` parameter that you specified in your application: 'false'. Available keys: ['disabledFeature', 'enabledFeature', 'intSetting', 'doubleSetting', 'stringSetting', 'objectSetting'].", $actualDetails->getError()?->getResolutionErrorMessage());
    }

    public function testFlagTypeMismatch(): void
    {
        $actualDetails = $this->provider->resolveBooleanValue('stringSetting', false, $this->evaluationContext);

        $this->assertFalse($actualDetails->getValue());
        $this->assertEquals(Reason::ERROR, $actualDetails->getReason());
        $this->assertEquals(ErrorCode::TYPE_MISMATCH(), $actualDetails->getError()?->getResolutionErrorCode());
    }
}
