// this job will run on every git push
job("Project Warm-Up for PhpStorm") {
    // ide is an IDE you want Space to build indexes for:
    // for JetBrains Fleet - Ide.Fleet
    // for IntelliJ-based IDEs via Gateway -
    // Ide.Idea, Ide.WebStorm, Ide.RubyMine,
    // Ide.CLion, Ide.GoLand, Ide.PhpStorm, Ide.PyCharm
    warmup(ide = Ide.PhpStorm)
}