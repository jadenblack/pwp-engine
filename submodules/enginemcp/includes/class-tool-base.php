<?php
/**
 * EngineMCP Tool Base Class
 * 
 * Base class for all MCP tools
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base class for MCP Tools
 */
abstract class EngineMCP_Tool_Base {

    /**
     * Tool ID
     */
    protected $id = '';
    
    /**
     * Tool name
     */
    protected $name = '';
    
    /**
     * Tool description
     */
    protected $description = '';
    
    /**
     * Tool category
     */
    protected $category = 'general';
    
    /**
     * Tool tags
     */
    protected $tags = [];
    
    /**
     * Get the tool ID
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Get the tool name
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Get the tool description
     */
    public function get_description() {
        return $this->description;
    }
    
    /**
     * Get the tool category
     */
    public function get_category() {
        return $this->category;
    }
    
    /**
     * Get the tool tags
     */
    public function get_tags() {
        return $this->tags;
    }
    
    /**
     * Get the tool schema
     * 
     * Returns a JSON Schema object describing the tool's parameters
     */
    abstract public function get_schema();
    
    /**
     * Execute the tool
     * 
     * @param array $params Parameters for the tool
     * @return mixed Result of tool execution
     */
    abstract public function execute($params);
    
    /**
     * Get the tool manifest
     * 
     * Returns the complete tool manifest for MCP
     */
    public function get_manifest() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'tags' => $this->tags,
            'schema' => $this->get_schema(),
        ];
    }
    
    /**
     * Validate parameters against schema
     * 
     * @param array $params Parameters to validate
     * @return bool|WP_Error True if valid, WP_Error if not
     */
    public function validate_params($params) {
        $schema = $this->get_schema();
        
        // If schema defines required properties, check they exist
        if (isset($schema['required']) && is_array($schema['required'])) {
            foreach ($schema['required'] as $required_param) {
                if (!isset($params[$required_param])) {
                    return new WP_Error(
                        'missing_required_param',
                        sprintf(__('Missing required parameter: %s', 'pilotwp'), $required_param)
                    );
                }
            }
        }
        
        // Check property types if specified
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($params as $param => $value) {
                if (isset($schema['properties'][$param])) {
                    $property = $schema['properties'][$param];
                    
                    // Type validation
                    if (isset($property['type'])) {
                        $valid = $this->validate_param_type($value, $property['type']);
                        
                        if (!$valid) {
                            return new WP_Error(
                                'invalid_param_type',
                                sprintf(
                                    __('Invalid type for parameter %s. Expected %s.', 'pilotwp'),
                                    $param,
                                    $property['type']
                                )
                            );
                        }
                    }
                    
                    // Enum validation
                    if (isset($property['enum']) && is_array($property['enum'])) {
                        if (!in_array($value, $property['enum'])) {
                            return new WP_Error(
                                'invalid_param_value',
                                sprintf(
                                    __('Invalid value for parameter %s. Must be one of: %s', 'pilotwp'),
                                    $param,
                                    implode(', ', $property['enum'])
                                )
                            );
                        }
                    }
                }
            }
        }
        
        return true;
    }
    
    /**
     * Validate a parameter type
     * 
     * @param mixed $value The value to validate
     * @param string $type The expected type
     * @return bool True if value matches type
     */
    protected function validate_param_type($value, $type) {
        switch ($type) {
            case 'string':
                return is_string($value);
                
            case 'number':
                return is_numeric($value);
                
            case 'integer':
                return is_int($value) || (is_string($value) && ctype_digit($value));
                
            case 'boolean':
                return is_bool($value) || (is_string($value) && in_array(strtolower($value), ['true', 'false', '1', '0']));
                
            case 'array':
                return is_array($value);
                
            case 'object':
                return is_array($value) || is_object($value);
                
            case 'null':
                return is_null($value);
                
            default:
                return true; // Unknown type, assume valid
        }
    }
    
    /**
     * Execute the tool with validation
     * 
     * @param array $params Parameters for the tool
     * @return mixed Result of tool execution or WP_Error
     */
    public function execute_with_validation($params) {
        // Validate parameters first
        $validation = $this->validate_params($params);
        
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        // Log tool execution
        enginemcp_log(sprintf('Executing tool: %s with params: %s', $this->id, json_encode($params)));
        
        try {
            // Execute the tool
            $result = $this->execute($params);
            
            // Log successful execution
            enginemcp_log(sprintf('Tool %s executed successfully', $this->id));
            
            return $result;
        } catch (Exception $e) {
            // Log error
            enginemcp_log(sprintf('Tool %s execution failed: %s', $this->id, $e->getMessage()), 'error');
            
            return new WP_Error(
                'tool_execution_failed',
                sprintf(__('Tool execution failed: %s', 'pilotwp'), $e->getMessage())
            );
        }
    }
}
