<?php

declare(strict_types=1);

namespace Brick\StructuredData;

/**
 * An item, such as a Thing in schema.org's vocabulary.
 */
final class Item
{
    /**
     * The global identifier of the item, if any.
     */
    private readonly ?string $id;

    /**
     * The types this Item implements, as URLs.
     *
     * @var string[]
     */
    private readonly array $types;

    /**
     * The properties, as a map of property name to list of values.
     *
     * @var array<string, array<Item|string>>
     */
    private array $properties = [];

    /**
     * Item constructor.
     *
     * @param string|null $id       An optional global identifier for the item.
     * @param string      ...$types The types this Item implements, as URLs, e.g. http://schema.org/Product .
     */
    public function __construct(?string $id, string ...$types)
    {
        $this->id    = $id;
        $this->types = $types;
    }

    /**
     * Returns the global identifier of the item, if any.
     *
     * @return string|null
     */
    public function getId() : ?string
    {
        return $this->id;
    }

    /**
     * Returns the list of types this Item implements.
     *
     * Each type is represented as a URL, e.g. http://schema.org/Product .
     *
     * @return string[]
     */
    public function getTypes() : array
    {
        return $this->types;
    }

    /**
     * Returns a map of property name to list of values.
     *
     * Property names are represented as URLs, e.g. http://schema.org/price .
     * Values are a list of Item instances or plain strings.
     *
     * @return array<string, array<Item|string>>
     */
    public function getProperties() : array
    {
        return $this->properties;
    }

    /**
     * Returns a list of values for the given property.
     *
     * The result is a list of Item instances or plain strings.
     * If the property does not exist, an empty array is returned.
     *
     * @param string $name
     *
     * @return array<Item|string>
     */
    public function getProperty(string $name) : array
    {
        return $this->properties[$name] ?? [];
    }

    /**
     * @param string      $name
     * @param Item|string $value
     *
     * @return void
     */
    public function addProperty(string $name, Item|string $value) : void
    {
        $this->properties[$name][] = $value;
    }
}
