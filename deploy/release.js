#!/usr/bin/env node

import { readFileSync, writeFileSync } from 'fs';
import { execSync } from 'child_process';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const rootDir = join(__dirname, '..');

/**
 * Get the current date in YYYY-MM-DD format
 */
function getCurrentDate() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

/**
 * Escape string for use in RegExp
 */
function escapeRegExp(value) {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Get repository URL from git remote origin
 */
function getRepositoryUrlFromRemote() {
  try {
    const remoteUrl = execSync('git remote get-url origin', {
      cwd: rootDir,
      encoding: 'utf8'
    }).trim();

    if (!remoteUrl) {
      throw new Error('Remote origin URL is empty');
    }

    // SCP-like syntax, e.g. git@github.com:owner/repo.git
    if (!remoteUrl.includes('://')) {
      const scpMatch = remoteUrl.match(/^(?:.+@)?([^:]+):(.+)$/);
      if (scpMatch) {
        const host = scpMatch[1];
        const repoPath = scpMatch[2].replace(/\.git$/, '').replace(/^\/+/, '').replace(/\/+$/, '');
        if (repoPath) {
          return `https://${host}/${repoPath}`;
        }
      }

      throw new Error(`Unsupported remote format: ${remoteUrl}`);
    }

    // URL syntax, e.g. https://github.com/owner/repo.git or ssh://git@github.com/owner/repo.git
    const parsedUrl = new URL(remoteUrl);
    const repoPath = parsedUrl.pathname.replace(/\.git$/, '').replace(/^\/+/, '').replace(/\/+$/, '');

    if (!repoPath) {
      throw new Error(`Unable to parse repository path from remote: ${remoteUrl}`);
    }

    return `https://${parsedUrl.hostname}/${repoPath}`;
  } catch (error) {
    throw new Error(`Unable to determine repository URL from git remote origin: ${error.message}`);
  }
}

/**
 * Update version in a file with specific pattern
 */
function updateVersionInFile(filePath, version, patterns) {
  console.log(`Updating version in ${filePath}...`);
  
  let content = readFileSync(filePath, 'utf8');
  
  patterns.forEach(pattern => {
    const regex = new RegExp(pattern.search, 'g');
    content = content.replace(regex, pattern.replace.replace('${version}', version));
  });
  
  writeFileSync(filePath, content, 'utf8');
  console.log(`✓ Updated ${filePath}`);
}

/**
 * Update CHANGELOG.md to replace Unreleased with current date and add new Unreleased section
 */
function updateChangelog(version) {
  console.log('Updating CHANGELOG.md...');
  
  const changelogPath = join(rootDir, 'CHANGELOG.md');
  let content = readFileSync(changelogPath, 'utf8');
  
  // Replace [Unreleased] with [version] - date and add new [Unreleased] section
  const currentDate = getCurrentDate();
  const unreleasedPattern = /## \[Unreleased\]/g;
  const replacement = `## [Unreleased]

## [${version}] - ${currentDate}`;
  
  content = content.replace(unreleasedPattern, replacement);
  
  // Update links at the bottom, including first-release fallback
  const repoUrl = getRepositoryUrlFromRemote();
  const unreleasedLinePattern = /^\[unreleased\]:\s+(.+)$/im;
  const unreleasedLineMatch = content.match(unreleasedLinePattern);
  let previousRef = null;

  if (unreleasedLineMatch) {
    const currentUnreleasedTarget = unreleasedLineMatch[1].trim();
    const compareMatch = currentUnreleasedTarget.match(/\/compare\/(.+?)\.\.\.HEAD$/);
    if (compareMatch) {
      previousRef = compareMatch[1];
    }

    content = content.replace(
      unreleasedLinePattern,
      `[unreleased]: ${repoUrl}/compare/${version}...HEAD`
    );
  } else {
    if (!content.endsWith('\n')) {
      content += '\n';
    }
    content += `\n[unreleased]: ${repoUrl}/compare/${version}...HEAD`;
  }

  // Add current version link once. For first release, link directly to tag page.
  const versionLinkPattern = new RegExp(`^\\[${escapeRegExp(version)}\\]:\\s+.+$`, 'im');
  if (!versionLinkPattern.test(content)) {
    const hasPreviousVersion = previousRef && previousRef !== '0.0.0' && previousRef !== version;
    const newVersionLink = hasPreviousVersion
      ? `[${version}]: ${repoUrl}/compare/${previousRef}...${version}`
      : `[${version}]: ${repoUrl}/releases/tag/${version}`;

    const updatedUnreleasedLine = content.match(/^\[unreleased\]:.*$/m);
    if (updatedUnreleasedLine) {
      const insertPosition = content.indexOf(updatedUnreleasedLine[0]) + updatedUnreleasedLine[0].length;
      content = content.slice(0, insertPosition) + `\n${newVersionLink}` + content.slice(insertPosition);
    } else {
      content += `\n${newVersionLink}`;
    }
  }
  
  writeFileSync(changelogPath, content, 'utf8');
  console.log(`✓ Updated CHANGELOG.md with version header and comparison links`);
}

/**
 * Execute git commands
 */
function executeGitCommands(version) {
  console.log('Creating git commit and tag...');
  
  try {
    // Add all changes
    console.log('Adding files to git...');
    execSync('git add .', { stdio: 'inherit', cwd: rootDir });
    
    // Commit changes following the pattern "Update VERSION for x.x.x"
    const commitMessage = `Update VERSION for ${version}`;
    console.log(`Committing changes: ${commitMessage}`);
    execSync(`git commit -m "${commitMessage}"`, { stdio: 'inherit', cwd: rootDir });
    
    // Create tag
    const tagName = version;
    console.log(`Creating tag: ${tagName}`);
    execSync(`git tag ${tagName}`, { stdio: 'inherit', cwd: rootDir });
    
    console.log('✓ Git commit and tag created successfully');
    
  } catch (error) {
    console.error('❌ Error during git operations:', error.message);
    throw error;
  }
}

/**
 * Main release function
 */
function release() {
  // Get version from command line argument
  const version = process.argv[2];
  
  if (!version) {
    console.error('❌ Please provide a version number');
    console.log('Usage: node release.js <version>');
    console.log('Example: node release.js 1.0.0');
    process.exit(1);
  }
  
  // Validate version format (basic semver check)
  const versionRegex = /^\d+\.\d+\.\d+$/;
  if (!versionRegex.test(version)) {
    console.error('❌ Invalid version format. Please use semantic versioning (e.g., 1.0.0)');
    process.exit(1);
  }
  
  console.log(`🚀 Starting release process for version ${version}...`);
  
  try {
    // Update readme.txt
    updateVersionInFile(join(rootDir, 'readme.txt'), version, [
      {
        search: 'Stable tag: [0-9]+\\.[0-9]+\\.[0-9]+',
        replace: `Stable tag: ${version}`
      }
    ]);
    
    // Update style.css
    updateVersionInFile(join(rootDir, 'style.css'), version, [
      {
        search: 'Version:\\s*[0-9]+\\.[0-9]+\\.[0-9]+',
        replace: `Version: ${version}`
      }
    ]);
    
    // Update CHANGELOG.md
    updateChangelog(version);
    
    // Execute git commands
    executeGitCommands(version);
    
    console.log('✅ Release process completed successfully!');
    console.log('');
    console.log('📋 Next steps:');
    console.log('1. Push changes: git push && git push --tags');
    
  } catch (error) {
    console.error('❌ Error during release process:', error.message);
    process.exit(1);
  }
}

// Run the release process
release();
