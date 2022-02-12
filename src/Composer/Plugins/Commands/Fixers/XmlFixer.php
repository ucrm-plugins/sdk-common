<?php /** @noinspection PhpUnused */
declare( strict_types=1 );

namespace SpaethTech\UCRM\SDK\Composer\Plugins\Commands\Fixers;

use Exception;
use SimpleXMLElement;

/**
 * Class XmlFixer
 *
 * @package   SpaethTech\UCRM\SDK
 *
 * @author    Ryan Spaeth (rspaeth@spaethtech.com)
 * @copyright 2022 Spaeth Technologies Inc.
 *
 */
class XmlFixer extends Fixer
{
    protected SimpleXMLElement $root;
    
    /**
     * Replaces attribute values in any element matching the provided XPath query.
     *
     * @param string $xpath             An XPath query.
     * @param array  $replaces          An array of attribute/value pair to use as replacements.
     *
     * @return $this                    Returns this {@see Fixer} with replaced values, for method chaining.
     * @throws Exception
     */
    public function replace( string $xpath, array $replaces ): self
    {
        if( !$this->root )
            $this->root = new SimpleXMLElement( $this->text );
        
        foreach( $this->root->xpath($xpath) as $element )
            foreach( $replaces as $attribute => $value )
                $element[$attribute] = $value;
        
        $this->text = $this->root->asXML();
        
        return $this;
    }
    
}
