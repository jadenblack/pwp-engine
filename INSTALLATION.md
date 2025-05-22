# PilotWP Engine Installation Guide

## 🚀 **Multiple Installation Methods**

Your PWP Engine plugin supports **both Composer and GitHub** management for maximum flexibility!

## 📦 **Method 1: Composer Installation (Recommended for LocalWP)**

### **Option A: Direct Composer Install**
```bash
# Navigate to your WordPress root
cd /path/to/your/wordpress

# Install via Composer
composer require jadenblack/pwp-engine

# Activate the plugin
wp plugin activate pilotwp
```

### **Option B: Add to existing composer.json**
```json
{
    "require": {
        "jadenblack/pwp-engine": "^2.0"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/jadenblack/pwp-engine"
        }
    ]
}
```

Then run:
```bash
composer install
wp plugin activate pilotwp
```

### **Option C: LocalWP with Composer**
```bash
# In LocalWP terminal
cd app/public

# If no composer.json exists, create one
echo '{
    "require": {
        "jadenblack/pwp-engine": "^2.0"
    },
    "repositories": [
        {
            "type": "vcs", 
            "url": "https://github.com/jadenblack/pwp-engine"
        }
    ],
    "extra": {
        "installer-paths": {
            "wp-content/plugins/{$name}/": ["type:wordpress-plugin"]
        }
    }
}' > composer.json

# Install
composer install

# Activate
wp plugin activate pilotwp
```

## 🐙 **Method 2: GitHub Direct Install**

### **Option A: WP-CLI GitHub Install**
```bash
# Install directly from GitHub
wp plugin install https://github.com/jadenblack/pwp-engine/archive/refs/heads/main.zip --activate
```

### **Option B: Manual Download**
```bash
# Download and extract
cd wp-content/plugins
curl -L -o pwp-engine.zip https://github.com/jadenblack/pwp-engine/archive/main.zip
unzip pwp-engine.zip
mv pwp-engine-main pilotwp
wp plugin activate pilotwp
```

### **Option C: Git Clone (Development)**
```bash
cd wp-content/plugins
git clone https://github.com/jadenblack/pwp-engine.git pilotwp
wp plugin activate pilotwp
```

## 🎯 **Method 3: WordPress Admin Upload**

1. **Download ZIP**: Go to https://github.com/jadenblack/pwp-engine/archive/main.zip
2. **WordPress Admin** → Plugins → Add New → Upload Plugin
3. **Upload the ZIP file** and activate

## 🔄 **Method 4: Via Plugin Manager (After First Install)**

Once PWP Engine is installed via any method above:

1. **WordPress Admin** → PilotWP → Plugin Manager
2. **Install GitHub Updater** (Quick Install button)
3. **Configure GitHub token** for private repos
4. **Enable auto-updates**

## 🏆 **Recommended Workflow by Environment**

### **🖥️ LocalWP Development**
```bash
# Best approach for LocalWP
composer require jadenblack/pwp-engine
wp plugin activate pilotwp

# Then use the plugin's manager for other plugins
# WordPress Admin → PilotWP → Plugin Manager → Development Setup
```

### **🌐 Production/Staging**
```bash
# Via WP-CLI (most reliable)
wp plugin install https://github.com/jadenblack/pwp-engine/archive/main.zip --activate

# Then configure GitHub Updater for automatic updates
```

### **👨‍💻 Development/Contributing** 
```bash
# Git clone for development
cd wp-content/plugins
git clone https://github.com/jadenblack/pwp-engine.git pilotwp
composer install  # Install dev dependencies
wp plugin activate pilotwp
```

## 🔧 **Post-Installation Setup**

After installation via any method:

### **1. Install Essential Development Tools**
```
WordPress Admin → PilotWP → Plugin Manager → Development Setup
Click "Install All Development Plugins"
```

### **2. Configure GitHub Updater**
```
WordPress Admin → PilotWP → Plugin Manager → GitHub Updater
Add your GitHub Personal Access Token
Enable auto-updates
```

### **3. Install MCP Servers**
```
WordPress Admin → PilotWP → EngineMCP
Click "Install Essential Servers" or select specific ones
Configure API keys as needed
```

## 🎯 **Why Both Methods?**

**Composer Benefits:**
- ✅ **Dependency management** and autoloading
- ✅ **Version constraints** and lock files  
- ✅ **LocalWP compatibility** (no conflicts)
- ✅ **Professional workflow** for developers
- ✅ **Easy updates** via `composer update`

**GitHub Direct Benefits:**
- ✅ **No Composer required** for simple sites
- ✅ **Direct access** to latest commits
- ✅ **WordPress.org style** installation
- ✅ **GitHub Updater integration** for auto-updates
- ✅ **Simple for non-developers**

## 🚀 **Best Practice Recommendation**

**For LocalWP Development:**
1. **Use Composer** for initial installation
2. **Enable GitHub Updater** for automatic updates
3. **Use Plugin Manager** for other development tools

**Commands:**
```bash
# Initial install
composer require jadenblack/pwp-engine
wp plugin activate pilotwp

# Let the plugin manage everything else through WordPress admin
```

This gives you the **best of both worlds**:
- Professional Composer dependency management
- Automatic GitHub updates 
- WordPress-native plugin management interface
- No conflicts with LocalWP's environment

Your plugin now supports **every possible installation method** while maintaining professional standards! 🎉
