<?php

namespace danog\PhpDoc\PhpDoc;

use danog\PhpDoc\PhpDoc;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use phpDocumentor\Reflection\DocBlock\Tags\InvalidTag;
use phpDocumentor\Reflection\DocBlock\Tags\Property;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;

/**
 * Class documentation builder.
 *
 * @internal
 */
class ClassDoc extends GenericDoc
{
    /**
     * Properties.
     *
     * @var array<string, array>
     */
    private array $properties = [];
    /**
     * Methods.
     *
     * @var array<string, MethodDoc>
     */
    private array $methods = [];
    /**
     * Constants.
     */
    private array $constants = [];
    public function __construct(PhpDoc $builder, ReflectionClass $reflectionClass)
    {
        $this->builder = $builder;
        $this->name = $reflectionClass->getName();
        $doc = $reflectionClass->getDocComment();
        if (!$doc) {
            \fprintf(STDERR, $reflectionClass->getName()." has no PHPDOC".PHP_EOL);
            $this->ignore = true;
            return;
        }
        $doc = $this->builder->getFactory()->create($doc);

        parent::__construct($doc, $reflectionClass);

        $tags = $doc->getTags();
        foreach ($tags as $tag) {
            if ($tag instanceof Property && $tag->getVariableName()) {
                /** @psalm-suppress InvalidPropertyAssignmentValue */
                $this->properties[$tag->getVariableName()] = [
                    $tag->getType(),
                    $tag->getDescription()
                ];
            }
            if ($tag instanceof InvalidTag && $tag->getName() === 'property') {
                [$type, $description] = \explode(" $", $tag->render(), 2);
                $description .= ' ';
                [$varName, $description] = \explode(" ", $description, 2);
                $type = \str_replace('@property ', '', $type);
                $description ??= '';
                /** @psalm-suppress InvalidPropertyAssignmentValue */
                $this->properties[$varName] = [
                    $type,
                    $description
                ];
            }
        }
        foreach ($reflectionClass->getConstants() as $key => $value) {
            $refl = new ReflectionClassConstant($reflectionClass->getName(), $key);
            if (!$refl->isPublic()) {
                continue;
            }
            $description = '';
            if ($refl->getDocComment()) {
                $docConst = $this->builder->getFactory()->create($refl->getDocComment());
                if ($this->builder->shouldIgnore($refl->getDeclaringClass()->getName())) {
                    continue;
                }
                foreach ($docConst->getTags() as $tag) {
                    if ($tag instanceof Generic && $tag->getName() === 'internal') {
                        continue 2;
                    }
                }
                $description .= $docConst->getSummary();
                if ($docConst->getDescription()) {
                    $description .= "\n\n";
                    $description .= $docConst->getDescription();
                }
            }
            $description = \trim($description);
            $this->constants[$key] = [
                $value,
                $description
            ];
        }


        foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (\str_starts_with($method->getName(), '__') && $method !== '__construct') {
                continue;
            }
            $this->methods[$method->getName()] = new MethodDoc($this->builder, $method);
        }

        $this->methods = \array_filter($this->methods, fn (MethodDoc $doc): bool => !$doc->shouldIgnore());
    }

    /**
     * Generate markdown for class.
     *
     * @return string
     */
    public function format(?string $namespace = null): string
    {
        $init = parent::format();
        if ($this->constants) {
            $init .= "\n";
            $init .= "## Constants\n";
            foreach ($this->constants as $name => [, $description]) {
                $description = \trim($description);
                $description = \str_replace("\n", "\n  ", $description);
                $init .= "* `{$this->className}::$name`: $description\n";
                $init .= "\n";
            }
        }
        if ($this->methods) {
            $init .= "\n";
            $init .= "## Method list:\n";
            foreach ($this->methods as $method) {
                $init .= "* `".$method->getSignature()."`\n";
            }
            $init .= "\n";
            $init .= "## Methods:\n";
            foreach ($this->methods as $method) {
                $init .= $method->format($namespace ?? $this->namespace);
                $init .= "\n";
            }
        }
        if ($this->properties) {
            $init .= "## Properties\n";
            foreach ($this->properties as $name => [$type, $description]) {
                $init .= "* `\$$name`: `$type` $description";
                $init .= "\n";
            }
        }
        $init .= "---\n";
        $init .= "Generated by [danog/phpdoc](https://phpdoc.daniil.it)\n";
        return $init;
    }
}
