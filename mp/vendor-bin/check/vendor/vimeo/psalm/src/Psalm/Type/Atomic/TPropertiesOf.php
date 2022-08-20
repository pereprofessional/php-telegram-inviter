<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Type that resolves to a keyed-array with properties of a class as keys and
 * their apropriate types as values.
 *
 * @psalm-type TokenName = 'properties-of'|'public-properties-of'|'protected-properties-of'|'private-properties-of'
 */
class TPropertiesOf extends Atomic
{
    // These should match the values of
    // `Psalm\Internal\Analyzer\ClassLikeAnalyzer::VISIBILITY_*`, as they are
    // used to compared against properties visibililty.
    public const VISIBILITY_PUBLIC = 1;
    public const VISIBILITY_PROTECTED = 2;
    public const VISIBILITY_PRIVATE = 3;

    /**
     * @var string
     */
    public $fq_classlike_name;
    /**
     * @var TNamedObject
     */
    public $classlike_type;
    /**
     * @var self::VISIBILITY_*|null
     */
    public $visibility_filter;

    /**
     * @return list<TokenName>
     */
    public static function tokenNames(): array
    {
        return [
            'properties-of',
            'public-properties-of',
            'protected-properties-of',
            'private-properties-of'
        ];
    }

    /**
     * @param TokenName $tokenName
     * @return self::VISIBILITY_*|null
     */
    public static function filterForTokenName(string $token_name): ?int
    {
        switch ($token_name) {
            case 'public-properties-of':
                return self::VISIBILITY_PUBLIC;
            case 'protected-properties-of':
                return self::VISIBILITY_PROTECTED;
            case 'private-properties-of':
                return self::VISIBILITY_PRIVATE;
            default:
                return null;
        }
    }

    /**
     * @return TokenName
     */
    public static function tokenNameForFilter(?int $visibility_filter): string
    {
        switch ($visibility_filter) {
            case self::VISIBILITY_PUBLIC:
                return 'public-properties-of';
            case self::VISIBILITY_PROTECTED:
                return 'protected-properties-of';
            case self::VISIBILITY_PRIVATE:
                return  'private-properties-of';
            default:
                return 'properties-of';
        }
    }

    /**
     * @param self::VISIBILITY_*|null $visibility_filter
     */
    public function __construct(
        string $fq_classlike_name,
        TNamedObject $classlike_type,
        ?int $visibility_filter
    ) {
        $this->fq_classlike_name = $fq_classlike_name;
        $this->classlike_type = $classlike_type;
        $this->visibility_filter = $visibility_filter;
    }

    public function getKey(bool $include_extra = true): string
    {
        return self::tokenNameForFilter($this->visibility_filter) . '<' . $this->classlike_type . '>';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id
    ): string {
        return $this->getKey();
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
