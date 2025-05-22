# PilotWP Engine

Enterprise-grade WordPress enhancement platform with submodule support for forms, security, MCP servers, and n8n integration.

## 🚀 Features

- **🤖 MCP Server Management**: Install and manage 15+ Model Context Protocol servers for AI assistants
- **🔌 Plugin Manager**: Multi-source plugin installation (WordPress.org, GitHub, Composer, Direct URLs)
- **🏗️ Modular Architecture**: Install only the features you need through submodules
- **🔗 GitHub Integration**: Automatic updates and plugin management from GitHub repositories
- **⚡ n8n Integration**: Seamless workflow automation capabilities
- **🛡️ Enterprise Security**: Advanced security and monitoring features
- **📋 Form Builder**: Professional form creation and management tools

## 📦 Installation

### **🎯 Quick Install (Recommended for LocalWP)**

```bash
# Via Composer (best for development)
composer require jadenblack/pwp-engine
wp plugin activate pilotwp

# Via WP-CLI (universal method)
wp plugin install https://github.com/jadenblack/pwp-engine/archive/main.zip --activate
```

### **📖 Full Installation Guide**

See [INSTALLATION.md](INSTALLATION.md) for complete installation methods including:
- Composer installation for LocalWP
- GitHub direct install
- WordPress admin upload
- Development setup

## 🏆 What Makes This Special

**PilotWP Engine** is the **first WordPress plugin** to provide:

1. **🤖 Complete MCP Ecosystem Integration** - 15 top AI servers with one-click install
2. **🔌 Multi-Source Plugin Management** - Install from GitHub, Composer, WordPress.org
3. **📱 WordPress-Native Interface** - Enterprise features in familiar WordPress admin
4. **💻 CLI Integration** - Full WP-CLI support for automation
5. **🏥 Health Monitoring** - Automatic server monitoring and notifications

## ⚡ Quick Start

1. **Install the plugin** (see methods above)
2. **Access PilotWP** → WordPress Admin → PilotWP
3. **Install MCP Servers** → EngineMCP tab → "Install Essential Servers"
4. **Configure Plugin Manager** → Plugin Manager tab → Install development tools
5. **Download configs** for Claude Desktop and Cursor AI

## 🏗️ Available Submodules

### **🤖 EngineMCP v2.0** - AI Server Management
- **15 Top MCP Servers**: Sequential Thinking, GitHub, DuckDuckGo Search, Memory systems, etc.
- **One-click installation** with automatic dependency management
- **Configuration generation** for Claude Desktop and Cursor
- **API key management** with secure storage
- **Health monitoring** and status tracking

### **🔌 Plugin Manager** - Multi-Source Installation
- **WordPress.org** repository plugins
- **GitHub** repository direct install
- **Composer** package management
- **Direct URL** ZIP file installation
- **GitHub Updater** integration for auto-updates

### **📋 PilotForms** (Coming Soon)
- Advanced form builder and management
- Conditional logic and multi-step forms
- Integration with popular services

### **🛡️ PilotSecurity** (Coming Soon)
- Enterprise security suite
- Advanced monitoring and protection

## 🎯 Use Cases

### **👨‍💻 For Developers**
```bash
# Install via Composer for clean dependency management
composer require jadenblack/pwp-engine

# Access via WordPress Admin
# PilotWP → EngineMCP → Install GitHub integration
# PilotWP → Plugin Manager → Development Setup
```

**Features:**
- Enhanced coding assistance with GitHub MCP integration
- File analysis and code search capabilities
- Browser automation for testing
- Terminal access through Desktop Commander

### **✍️ For Content Creators**
- Web research with DuckDuckGo integration
- Image generation with Flux ImageGen
- Diagram creation with Mermaid generator
- Memory persistence across AI sessions

### **🏢 For Businesses**
- Workflow automation with Apify Actors (3000+ tools)
- Data extraction and processing
- Multi-agent problem solving
- Custom tool development

## 💻 CLI Usage

### **MCP Server Management**
```bash
# Install essential MCP servers
wp enginemcp install --essential

# List available servers
wp enginemcp list

# Test server functionality
wp enginemcp test sequential-thinking

# Generate configuration files
wp enginemcp config --type=claude --output=/tmp

# Check system status
wp enginemcp status
```

### **Plugin Management**
```bash
# Install from GitHub
wp pilotwp plugin install --source=github --url=https://github.com/user/repo

# Install from WordPress.org
wp pilotwp plugin install --source=wordpress.org --slug=plugin-name

# Bulk install development plugins
wp pilotwp setup-dev
```

## 🔧 API Integration

### **WordPress Hooks**
```php
// Add custom MCP servers
add_filter('enginemcp_servers', function($servers) {
    $servers['my-server'] = [
        'package' => '@company/mcp-server',
        'name' => 'My Custom Server',
        'description' => 'Custom functionality',
        'priority' => 20
    ];
    return $servers;
});

// Hook into installations
add_action('enginemcp_servers_installed', function($results) {
    // Custom logic after MCP server installation
});
```

### **REST API Endpoints**
```javascript
// Get installation status
GET /wp-json/pilotwp/v1/status

// Install MCP servers
POST /wp-json/pilotwp/v1/mcp/install
{
    "servers": ["sequential-thinking", "github"],
    "activate": true
}

// Generate configurations
GET /wp-json/pilotwp/v1/mcp/config/claude
```

## 📊 Supported MCP Servers

| Server | Description | API Key Required |
|--------|-------------|------------------|
| **Sequential Thinking** | Structured problem-solving | ❌ |
| **GitHub** | Repository management | ✅ |
| **DuckDuckGo Search** | Web search capabilities | ❌ |
| **Browserbase** | Cloud browser automation | ✅ |
| **Memory Bank** | Persistent memory | ❌ |
| **Playwright** | Browser automation | ❌ |
| **Flux ImageGen** | AI image generation | ❌ |
| **Apify Actors** | 3000+ web tools | ✅ |
| **+ 7 more servers** | [See full list](submodules/enginemcp/README.md) | |

## 🔐 Requirements

- **WordPress 5.9+**
- **PHP 7.4+**
- **Node.js 16+** and **npm** (for MCP server installation)
- **Composer** (optional, for package management)

## 🤝 Contributing

1. **Fork the repository**
2. **Create feature branch**: `git checkout -b feature/amazing-feature`
3. **Commit changes**: `git commit -m 'Add amazing feature'`
4. **Push to branch**: `git push origin feature/amazing-feature`
5. **Open Pull Request**

## 📝 License

GPL-3.0+ - see [LICENSE](LICENSE) file for details.

## 🔗 Links

- **GitHub**: https://github.com/jadenblack/pwp-engine
- **Issues**: https://github.com/jadenblack/pwp-engine/issues
- **Documentation**: [Full docs](submodules/enginemcp/README.md)
- **Installation Guide**: [INSTALLATION.md](INSTALLATION.md)

## 🎉 Ready to Transform Your WordPress Workflow?

**PilotWP Engine** brings enterprise-grade AI capabilities and professional plugin management directly into WordPress.

**Install now** and experience the future of WordPress development! 🚀

---

*Enterprise-grade WordPress enhancement platform • Built for developers, creators, and businesses*
