# Packagist Publication Guide

## 🚀 How to Publish PWP Engine to Packagist

To make `composer require jadenblack/pwp-engine` work globally:

### 1. **Create Packagist Account**
- Go to https://packagist.org/
- Sign up with your GitHub account

### 2. **Submit Package**
- Click "Submit" on Packagist
- Enter: `https://github.com/jadenblack/pwp-engine`
- Click "Check"
- If validation passes, click "Submit"

### 3. **Set Up Auto-Updates**
- Go to your package page on Packagist
- Click "Settings"
- Add GitHub Service Hook or use GitHub Token

### 4. **Tag a Release**
```bash
git tag -a v2.0.0 -m "Release v2.0.0"
git push origin v2.0.0
```

### 5. **Update composer.json**
```json
{
    "name": "jadenblack/pwp-engine",
    "version": "2.0.0"
}
```

After publication, users can install with:
```bash
composer require jadenblack/pwp-engine
```

### Alternative: Private Packagist
For private/enterprise use:
- Use Private Packagist (paid)
- Or maintain VCS repository method
