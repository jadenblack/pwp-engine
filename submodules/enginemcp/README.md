# EngineMCP v2.0 - Complete MCP Server Management

## 🚀 What We've Built

**EngineMCP v2.0** now includes **complete installation and management** for the **top 15 MCP servers**, integrated directly into your PilotWP WordPress plugin!

## ✨ New Features

### 🔧 **Comprehensive MCP Server Installation**
- **15 Top MCP Servers** ready to install with one click
- **Automatic dependency management** (Node.js, npm packages)
- **Smart installation** with fallback strategies
- **Health monitoring** and status checking

### 🎛️ **WordPress Admin Dashboard**
- **Tabbed interface** for easy management
- **Bulk installation** options (All servers, Essential only, Custom selection)
- **API key management** with secure storage
- **Configuration file generation** for Claude Desktop and Cursor
- **Real-time status monitoring**

### 💻 **WP-CLI Integration**
Complete command-line interface for power users:
```bash
wp enginemcp install --essential
wp enginemcp list --installed
wp enginemcp test sequential-thinking
wp enginemcp apikey set GITHUB_TOKEN ghp_1234567890
wp enginemcp config --type=claude --output=/tmp
wp enginemcp status
```

### 📥 **One-Click Configuration Downloads**
- **Claude Desktop** configuration (`claude_desktop_config.json`)
- **Cursor** configuration (`mcp.json`) 
- **Installation script** (`install_mcp_servers.sh`)
- **API keys setup guide**

## 🏆 Supported MCP Servers

| Priority | Server Name | Description | API Key Required |
|----------|-------------|-------------|------------------|
| 1 | **Sequential Thinking** | Dynamic problem-solving through structured thinking | ❌ |
| 2 | **Browserbase** | Cloud browser automation capabilities | ✅ |
| 3 | **DuckDuckGo Search** | Web search capabilities | ❌ |
| 4 | **GitHub** | Repository management and file operations | ✅ |
| 5 | **Knowledge Graph Memory** | Local knowledge graph for persistent memory | ❌ |
| 6 | **Memory Bank** | Remote memory management across sessions | ❌ |
| 7 | **Playwright Automation** | Browser automation using Playwright | ❌ |
| 8 | **Think Tool Server** | Enhanced reasoning capabilities | ❌ |
| 9 | **Multi-Agent Sequential Thinking** | Multiple AI agents working together | ✅ |
| 10 | **Apify Actors** | Connect to 3000+ pre-built cloud tools | ✅ |
| 11 | **Flux ImageGen** | Generate and manipulate images using AI | ❌ |
| 12 | **File Context Server** | Read, search, and analyze code files | ❌ |
| 13 | **Drawing Tool** | Create drawings with canvas interface | ❌ |
| 14 | **Mermaid Diagram Generator** | Convert text to high-quality PNG diagrams | ❌ |
| 15 | **Desktop Commander** | Execute terminal commands and manage files | ❌ |

## 🚀 Quick Start

### 1. **Access EngineMCP Dashboard**
Navigate to: `WordPress Admin > PilotWP > EngineMCP`

### 2. **Install Essential Servers**
```bash
# Via WordPress Admin: Click "Install Essential Servers"
# Via WP-CLI:
wp enginemcp install --essential
```

### 3. **Configure API Keys**
Go to the **API Configuration** tab and add your keys:
- **GitHub Token** (for GitHub integration)
- **Browserbase API Key** (for cloud browser automation)
- **DeepSeek API Key** (for multi-agent thinking)
- **Apify Token** (for 3000+ web tools)

### 4. **Download Configuration Files**
1. Go to **Download Configs** tab
2. Download `claude_desktop_config.json` 
3. Download `mcp.json` for Cursor
4. Place files in the correct locations

### 5. **Test Your Installation**
```bash
wp enginemcp test
# Or use the "Test" buttons in WordPress admin
```

## 📂 File Locations

### **Claude Desktop Configuration**
- **macOS:** `~/Library/Application Support/Claude/claude_desktop_config.json`
- **Windows:** `%APPDATA%/Claude/claude_desktop_config.json`

### **Cursor Configuration**  
- **Global:** `~/.cursor/mcp.json`
- **Project:** `.cursor/mcp.json` (in project root)

## 🔧 Advanced Usage

### **CLI Commands**

```bash
# Install specific servers
wp enginemcp install --servers=sequential-thinking,github,duckduckgo-search

# List all available servers  
wp enginemcp list --format=json

# Test a specific server
wp enginemcp test github

# Manage API keys
wp enginemcp apikey list
wp enginemcp apikey set GITHUB_TOKEN your_token_here

# Generate configuration files
wp enginemcp config --type=both --output=/tmp

# Check system status
wp enginemcp status

# Uninstall servers
wp enginemcp uninstall sequential-thinking
wp enginemcp uninstall --all --force
```

### **WordPress Hooks & Filters**

```php
// Add custom MCP servers
add_filter('enginemcp_servers', function($servers) {
    $servers['my-custom-server'] = [
        'package' => '@mycompany/custom-mcp-server',
        'name' => 'My Custom Server',
        'description' => 'Custom functionality',
        'requires_api_key' => false,
        'priority' => 20
    ];
    return $servers;
});

// Hook into installation completion
add_action('enginemcp_servers_installed', function($results) {
    // Custom logic after server installation
    error_log('MCP Servers installed: ' . print_r($results, true));
});
```

## 🔍 Health Monitoring

EngineMCP includes **automatic health monitoring**:

- **Weekly health checks** of installed servers
- **Email notifications** when servers fail
- **Status tracking** in WordPress admin
- **CLI status commands** for quick checks

## 🛠️ Technical Features

### **Smart Installation**
- **Dependency checking** (Node.js, npm availability)
- **Fallback strategies** (global install → npx caching)
- **Error handling** with detailed reporting
- **Bulk operations** with progress tracking

### **Security**
- **API key encryption** in WordPress options
- **Nonce verification** for all AJAX requests
- **Capability checking** (`manage_options`)
- **Input sanitization** throughout

### **Performance**
- **Lazy loading** of MCP components
- **Caching** of server status
- **Background processing** for installations
- **Optimized queries** and minimal overhead

## 🔄 Upgrade Path

EngineMCP v2.0 is **backward compatible** with existing installations:

1. **Existing functionality** remains unchanged
2. **New MCP installer** is additive
3. **Optional features** don't affect core operations  
4. **Gradual migration** to new features possible

## 📋 Requirements

- **WordPress 5.9+**
- **PHP 7.4+**
- **Node.js 16+** and **npm** (for MCP server installation)
- **PilotWP Engine** plugin installed

## 🎯 Use Cases

### **For Developers**
- **Enhanced coding assistance** with GitHub integration
- **File analysis** and code search capabilities
- **Browser automation** for testing
- **Terminal access** through Desktop Commander

### **For Content Creators**
- **Web research** with DuckDuckGo integration
- **Image generation** with Flux ImageGen
- **Diagram creation** with Mermaid generator
- **Memory persistence** across sessions

### **For Businesses**
- **Workflow automation** with Apify Actors
- **Data extraction** and processing
- **Multi-agent problem solving**
- **Custom tool development**

## 🔮 Future Enhancements

- **Custom MCP server builder** (visual interface)
- **Server templates** and presets
- **Performance analytics** and optimization
- **Integration with** more AI platforms
- **Marketplace** for community servers
- **Auto-updates** for installed servers

## 🤝 Integration Examples

### **Using with Claude Desktop**
```json
{
  "mcpServers": {
    "sequential-thinking": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-sequential-thinking"]
    },
    "github": {
      "command": "npx", 
      "args": ["-y", "@anthropic-samples/github-mcp-server"],
      "env": {
        "GITHUB_TOKEN": "your_github_token_here"
      }
    }
  }
}
```

### **Using with Cursor**
```json
{
  "mcpServers": {
    "duckduckgo-search": {
      "command": "npx",
      "args": ["-y", "@nickclyde/duckduckgo-mcp-server"]
    },
    "file-context": {
      "command": "npx",
      "args": ["-y", "@bsmi021/mcp-file-context-server"]
    }
  }
}
```

## 📊 Statistics & Monitoring

EngineMCP provides detailed insights:

- **Installation success rates**
- **Server response times** 
- **API usage statistics**
- **Error logs** and debugging info
- **Performance metrics**

## 🔐 Security Considerations

- **API keys** are stored securely in WordPress options
- **No credentials** are transmitted to external services
- **Local installation** keeps your data private
- **Audit logs** for all administrative actions
- **Role-based access** control

## 🚨 Troubleshooting

### **Common Issues**

**Node.js not found:**
```bash
# Install Node.js from https://nodejs.org/
node --version  # Should show version number
npm --version   # Should show version number
```

**Server installation fails:**
```bash
# Try manual installation
wp enginemcp test
wp enginemcp install --servers=sequential-thinking --force
```

**API key issues:**
```bash
# Check API key configuration
wp enginemcp apikey list
wp enginemcp apikey set GITHUB_TOKEN your_new_token
```

**Configuration not working:**
```bash
# Regenerate config files
wp enginemcp config --type=both --output=/tmp
# Copy files to correct locations manually
```

## 📞 Support & Documentation

- **WordPress Admin:** Built-in help and status information
- **WP-CLI:** `wp help enginemcp` for command documentation
- **GitHub Issues:** Report bugs and feature requests
- **Community:** Join discussions about MCP integration

## 🏆 What Makes This Special?

This is the **first WordPress plugin** to provide:

1. **Complete MCP ecosystem integration**
2. **One-click installation** of multiple MCP servers
3. **WordPress-native management interface**
4. **WP-CLI commands** for automation
5. **Enterprise-grade monitoring** and health checks

## 🎉 Ready to Transform Your AI Workflow?

**EngineMCP v2.0** brings the power of the **Model Context Protocol** directly into WordPress, making advanced AI capabilities accessible to everyone.

**Install now** and experience the future of AI-powered development! 🚀

---

*Part of the PilotWP Engine - Enterprise-grade WordPress enhancement platform*
